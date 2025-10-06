<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Contact;
use App\Business;
use App\Transaction;
use App\NotificationTemplate;
use App\Utils\NotificationUtil;
use App\Utils\Util;
use Illuminate\Support\Facades\Log;
use DB;

class SendMonthlyPaymentLinkSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contact;
    protected $business;
    
    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param Contact $contact
     * @param Business $business
     * @return void
     */
    public function __construct(Contact $contact, Business $business)
    {
        $this->contact = $contact;
        $this->business = $business;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Check if contact has unpaid invoices
            $unpaid_invoices = $this->getUnpaidInvoicesForContact();
            
            if ($unpaid_invoices->isEmpty()) {
                Log::info("No unpaid invoices for contact {$this->contact->id}, skipping SMS");
                return;
            }

            // Ensure payment token exists
            if (empty($this->contact->payment_token)) {
                $this->contact->generatePaymentToken();
                $this->contact->refresh();
            }

            // Get notification template
            $template = NotificationTemplate::getTemplate($this->business->id, 'monthly_payment_link');
            
            if (empty($template['sms_body'])) {
                Log::warning("No SMS template configured for monthly payment link for business {$this->business->id}");
                return;
            }

            // Calculate totals
            $total_due = $unpaid_invoices->sum('due_amount');
            $invoice_count = $unpaid_invoices->count();
            
            // Generate payment link
            $payment_link = route('public-payment.show', $this->contact->payment_token);

            // Prepare SMS content with template tags
            $sms_content = $this->replaceTemplateTags($template['sms_body'], $total_due, $invoice_count, $payment_link);

            // Send SMS using existing SMS utility
            $util = new Util();
            $sms_settings = $this->business->sms_settings ?: [];
            if (is_string($sms_settings)) {
                $sms_settings = json_decode($sms_settings, true) ?: [];
            }
            
            if (empty($sms_settings) || empty($sms_settings['sms_service'])) {
                Log::warning("SMS settings not configured for business {$this->business->id}");
                return;
            }

            // Format mobile number with 960 prefix for Maldivian numbers
            $mobile_number = $this->contact->mobile;
            if (!empty($mobile_number)) {
                // Remove + prefix if exists
                if (str_starts_with($mobile_number, '+960')) {
                    $mobile_number = substr($mobile_number, 1); // Remove +, keep 960
                } elseif (str_starts_with($mobile_number, '+')) {
                    $mobile_number = substr($mobile_number, 1); // Remove + from other countries
                }
                
                // Add 960 if not already present and looks like Maldivian number (7 digits)
                if (!str_starts_with($mobile_number, '960') && strlen($mobile_number) == 7) {
                    $mobile_number = '960' . $mobile_number;
                }
            }

            // Prepare data array for sendSms method
            $data = [
                'sms_body' => $sms_content,
                'mobile_number' => $mobile_number,
                'sms_settings' => $sms_settings
            ];

            $result = $util->sendSms($data);
            
            Log::info("Monthly payment link SMS sent to contact {$this->contact->id}, mobile: {$mobile_number} (original: {$this->contact->mobile}), result: " . json_encode($result));
            
        } catch (\Exception $e) {
            Log::error("Failed to send monthly payment link SMS to contact {$this->contact->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get unpaid invoices for the contact
     */
    private function getUnpaidInvoicesForContact()
    {
        // Skip if this is a walk-in customer
        if ($this->contact->is_default == 1) {
            return collect();
        }
        
        return Transaction::where('contact_id', $this->contact->id)
            ->where('business_id', $this->business->id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereIn('payment_status', ['due', 'partial'])
            ->select([
                'id',
                'invoice_no',
                'transaction_date',
                'final_total',
                DB::raw('(SELECT COALESCE(SUM(tp.amount), 0) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id) as total_paid'),
                DB::raw('(final_total - (SELECT COALESCE(SUM(tp.amount), 0) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id)) as due_amount')
            ])
            ->having('due_amount', '>', 0)
            ->orderBy('transaction_date', 'asc')
            ->get();
    }

    /**
     * Replace template tags with actual values
     */
    private function replaceTemplateTags($content, $total_due, $invoice_count, $payment_link)
    {
        $tags = [
            '{business_name}' => $this->business->name,
            '{contact_name}' => $this->contact->name,
            '{total_due_amount}' => number_format($total_due, 2),
            '{invoice_count}' => $invoice_count,
            '{payment_link}' => $payment_link,
            '{due_date}' => date('Y-m-d'),
        ];

        // Add contact custom fields
        for ($i = 1; $i <= 10; $i++) {
            $custom_field = 'custom_field' . $i;
            $tags['{contact_custom_field_' . $i . '}'] = $this->contact->$custom_field ?? '';
        }

        return str_replace(array_keys($tags), array_values($tags), $content);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("SendMonthlyPaymentLinkSms job failed for contact {$this->contact->id}: " . $exception->getMessage());
    }
}
