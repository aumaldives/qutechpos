<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendMonthlyPaymentLinkSms;
use App\Business;
use App\Contact;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendMonthlyPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:monthly-payment-reminders {--business-id=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send monthly payment reminder SMS to customers with outstanding invoices';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting monthly payment reminders...');
        
        try {
            // Use application timezone instead of system UTC
            $current_time = Carbon::now(config('app.timezone'));
            $current_day = $current_time->day;
            $current_hour = $current_time->hour;
            $current_minute = $current_time->minute;
            
            $force = $this->option('force');
            $business_id = $this->option('business-id');

            // Build query for schedules that should run today
            $query = DB::table('monthly_sms_schedules as ms')
                ->join('business as b', 'ms.business_id', '=', 'b.id')
                ->where('ms.is_enabled', 1);
                
            if ($business_id) {
                $query->where('ms.business_id', $business_id);
            }
            
            if (!$force) {
                // Handle month-end edge cases (e.g., 31st in February becomes last day of month)
                $last_day_of_month = $current_time->copy()->endOfMonth()->day;
                
                $query->where(function($q) use ($current_day, $last_day_of_month) {
                        // Exact day match
                        $q->where('ms.send_day', $current_day);
                        
                        // OR if today is the last day of month and schedule is for a day beyond this month's length
                        if ($current_day == $last_day_of_month) {
                            $q->orWhere('ms.send_day', '>', $last_day_of_month);
                        }
                      })
                      ->where(function($q) use ($current_hour, $current_minute) {
                          $q->where(function($sq) use ($current_hour, $current_minute) {
                              $sq->whereRaw('HOUR(ms.send_time) = ?', [$current_hour])
                                 ->whereRaw('ABS(MINUTE(ms.send_time) - ?) <= 30', [$current_minute]);
                          });
                      })
                      // Don't send if already sent today
                      ->where(function($q) use ($current_time) {
                          $q->whereNull('ms.last_sent_at')
                            ->orWhere('ms.last_sent_at', '<', $current_time->copy()->startOfDay());
                      });
            }

            $schedules = $query->select([
                'ms.id',
                'ms.business_id',
                'ms.send_day',
                'ms.send_time',
                'b.name as business_name',
                'b.sms_settings'
            ])->get();

            if ($schedules->isEmpty()) {
                $this->info('No schedules to process at this time.');
                return Command::SUCCESS;
            }

            $total_jobs = 0;
            $total_contacts = 0;

            foreach ($schedules as $schedule) {
                $this->info("Processing schedule for business: {$schedule->business_name} (ID: {$schedule->business_id})");
                
                $business = Business::find($schedule->business_id);
                if (!$business) {
                    $this->warn("Business not found: {$schedule->business_id}");
                    continue;
                }

                // Check if SMS is properly configured
                $sms_settings = $business->sms_settings ?: [];
                if (is_string($sms_settings)) {
                    $sms_settings = json_decode($sms_settings, true) ?: [];
                }
                if (empty($sms_settings) || empty($sms_settings['sms_service'])) {
                    $this->warn("SMS not configured for business: {$business->name}");
                    continue;
                }

                // Get all customers with outstanding invoices
                $customers_with_dues = $this->getCustomersWithDues($schedule->business_id);
                
                if ($customers_with_dues->isEmpty()) {
                    $this->info("No customers with outstanding dues for business: {$business->name}");
                    continue;
                }

                $this->info("Found {$customers_with_dues->count()} customers with outstanding invoices");

                // Dispatch jobs for each customer
                foreach ($customers_with_dues as $contact_id) {
                    $contact = Contact::find($contact_id);
                    if ($contact && !empty($contact->mobile)) {
                        SendMonthlyPaymentLinkSms::dispatch($contact, $business)->delay(
                            Carbon::now()->addSeconds($total_jobs * 2) // Stagger jobs by 2 seconds
                        );
                        $total_jobs++;
                    }
                }

                $total_contacts += $customers_with_dues->count();

                // Update last_sent_at with application timezone
                DB::table('monthly_sms_schedules')
                    ->where('id', $schedule->id)
                    ->update(['last_sent_at' => $current_time->copy()]);

                $this->info("Dispatched {$customers_with_dues->count()} SMS jobs for business: {$business->name}");
            }

            $this->info("Successfully dispatched {$total_jobs} SMS jobs for {$total_contacts} customers across {$schedules->count()} businesses");
            
            Log::info("Monthly payment reminders dispatched: {$total_jobs} jobs for {$total_contacts} customers");
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error processing monthly payment reminders: " . $e->getMessage());
            Log::error("Monthly payment reminders failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get customers with outstanding dues for a business
     */
    private function getCustomersWithDues($business_id)
    {
        return DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereIn('t.payment_status', ['due', 'partial'])
            ->whereNotNull('c.mobile')
            ->where('c.mobile', '!=', '')
            ->where('c.is_default', '!=', 1)  // Exclude walk-in customers
            ->select('c.id')
            ->distinct()
            ->pluck('id');
    }
}
