<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Contact;
use App\Business;
use App\NotificationTemplate;
use App\Utils\Util;
use Illuminate\Support\Facades\Log;

class SendPaymentApprovedSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contact;
    protected $business;
    protected $payment_data;
    
    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param Contact $contact
     * @param Business $business
     * @param array $payment_data
     * @return void
     */
    public function __construct(Contact $contact, Business $business, array $payment_data)
    {
        $this->contact = $contact;
        $this->business = $business;
        $this->payment_data = $payment_data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Get notification template
            $template = NotificationTemplate::getTemplate($this->business->id, 'payment_approved');
            
            if (empty($template['sms_body'])) {
                Log::warning("No SMS template configured for payment approved for business {$this->business->id}");
                return;
            }

            // Prepare SMS content with template tags
            $sms_content = $this->replaceTemplateTags($template['sms_body']);

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
            
            Log::info("Payment approved SMS sent to contact {$this->contact->id}, mobile: {$mobile_number} (original: {$this->contact->mobile}), result: " . json_encode($result));
            
        } catch (\Exception $e) {
            Log::error("Failed to send payment approved SMS to contact {$this->contact->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Replace template tags with actual values
     */
    private function replaceTemplateTags($content)
    {
        $tags = [
            '{business_name}' => $this->business->name,
            '{contact_name}' => $this->contact->name,
            '{payment_amount}' => number_format($this->payment_data['amount'], 2),
            '{payment_method}' => $this->payment_data['method'] ?? 'Bank Transfer',
            '{payment_ref_number}' => $this->payment_data['reference'] ?? '',
            '{approval_date}' => date('Y-m-d'),
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
        Log::error("SendPaymentApprovedSms job failed for contact {$this->contact->id}: " . $exception->getMessage());
    }
}
