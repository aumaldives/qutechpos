<?php

namespace App\Http\Controllers;

use App\Contact;
use App\Transaction;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicPaymentController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Show public payment form for a customer
     */
    public function show($token)
    {
        $contact = Contact::where('payment_token', $token)->firstOrFail();
        
        // Get unpaid/partially paid invoices for this contact
        $unpaid_invoices = $this->getUnpaidInvoices($contact->id, $contact->business_id);
        
        if ($unpaid_invoices->isEmpty()) {
            return view('public_payment.no_invoices', compact('contact'));
        }
        
        // Get business and location info for bank accounts
        $business = \App\Business::find($contact->business_id);
        
        // Try to get a location, fallback to first available business location
        $location = $business->locations()->first();
        $location_id = $location ? $location->id : null;
        
        // Get bank accounts for this business/location
        $bank_accounts = $this->getBankAccountsForLocation($contact->business_id, $location_id);
        
        return view('public_payment.index', compact('contact', 'unpaid_invoices', 'bank_accounts'));
    }

    /**
     * Submit payment for processing
     */
    public function submit(Request $request, $token)
    {
        $contact = Contact::where('payment_token', $token)->firstOrFail();
        
        // Get bank accounts for validation
        $business = \App\Business::find($contact->business_id);
        $location_id = $business->locations()->first()->id ?? null;
        $bank_accounts = $this->getBankAccountsForLocation($contact->business_id, $location_id);

        $rules = [
            'total_amount' => 'required|numeric|min:0.01',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_mobile' => 'nullable|string|max:20',
            'receipt_file' => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:1000'
        ];

        // Only require bank_account_id if bank accounts are available
        if (!empty($bank_accounts)) {
            $rules['bank_account_id'] = 'required|integer';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            // Handle file upload
            $receipt_file_path = null;
            if ($request->hasFile('receipt_file')) {
                $file = $request->file('receipt_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                
                // Store in public_payments directory
                $upload_path = public_path('public_payments');
                if (!file_exists($upload_path)) {
                    mkdir($upload_path, 0777, true);
                }
                
                $file->move($upload_path, $filename);
                $receipt_file_path = 'public_payments/' . $filename;
            }

            // Create payment submission
            $submission = DB::table('public_payment_submissions')->insertGetId([
                'business_id' => $contact->business_id,
                'contact_id' => $contact->id,
                'total_amount' => $request->total_amount,
                'payment_method' => 'bank_transfer',
                'bank_account_id' => $request->bank_account_id ?? null,
                'receipt_file_path' => $receipt_file_path,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_mobile' => $request->customer_mobile,
                'notes' => $request->notes,
                'status' => 'pending',
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Auto-allocate payment to invoices
            $allocations = $this->autoAllocatePayment($contact->id, $contact->business_id, $request->total_amount);
            
            // Store invoice mappings
            foreach ($allocations as $allocation) {
                DB::table('public_payment_invoice_mappings')->insert([
                    'payment_submission_id' => $submission,
                    'transaction_id' => $allocation['transaction_id'],
                    'applied_amount' => $allocation['applied_amount'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return view('public_payment.success', [
                'contact' => $contact,
                'submission_id' => $submission,
                'total_amount' => $request->total_amount,
                'allocations' => $allocations
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->withErrors([
                'error' => 'Failed to submit payment. Please try again.'
            ])->withInput();
        }
    }

    /**
     * Get unpaid invoices for a contact
     */
    private function getUnpaidInvoices($contact_id, $business_id)
    {
        return Transaction::where('contact_id', $contact_id)
            ->where('business_id', $business_id)
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
            ->orderBy('transaction_date', 'asc')
            ->get();
    }

    /**
     * Auto-allocate payment amount to invoices (FIFO)
     */
    private function autoAllocatePayment($contact_id, $business_id, $total_amount)
    {
        $unpaid_invoices = $this->getUnpaidInvoices($contact_id, $business_id);
        $remaining_amount = $total_amount;
        $allocations = [];

        foreach ($unpaid_invoices as $invoice) {
            if ($remaining_amount <= 0) break;

            $due_amount = $invoice->due_amount;
            $payment_amount = min($remaining_amount, $due_amount);

            if ($payment_amount > 0) {
                $allocations[] = [
                    'transaction_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'due_amount' => $due_amount,
                    'applied_amount' => $payment_amount,
                    'remaining_balance' => $due_amount - $payment_amount
                ];

                $remaining_amount -= $payment_amount;
            }
        }

        return $allocations;
    }

    /**
     * Download invoice PDF for public payment portal
     */
    public function downloadInvoice($token, $invoice_id)
    {
        // Validate token
        $contact = Contact::where('payment_token', $token)->first();
        
        if (!$contact) {
            abort(404, 'Invalid payment token');
        }

        // Validate invoice belongs to this contact
        $transaction = Transaction::where('id', $invoice_id)
            ->where('contact_id', $contact->id)
            ->where('business_id', $contact->business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->first();
            
        if (!$transaction) {
            abort(404, 'Invoice not found');
        }

        // Use the existing TransactionUtil method for 100% consistency
        $transactionUtil = new TransactionUtil();
        $is_email_attachment = false;
        $mpdf = $transactionUtil->getEmailAttachmentForGivenTransaction($contact->business_id, $invoice_id, $is_email_attachment);
        
        // Get invoice number for filename
        $receipt_contents = $transactionUtil->getPdfContentsForGivenTransaction($contact->business_id, $invoice_id);
        $filename = $receipt_contents['receipt_details']->invoice_no . '.pdf';
        
        return response()->stream(function() use ($mpdf) {
            echo $mpdf->Output('', 'S');
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    /**
     * Get bank accounts configured for a specific business/location
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @return array
     */
    private function getBankAccountsForLocation($business_id, $location_id)
    {
        // First check for location-specific accounts
        $accounts = DB::table('business_bank_accounts')
            ->leftJoin('system_banks', 'business_bank_accounts.bank_id', '=', 'system_banks.id')
            ->where('business_bank_accounts.business_id', $business_id)
            ->where('business_bank_accounts.location_id', $location_id)
            ->where('business_bank_accounts.is_active', 1)
            ->select(
                'business_bank_accounts.id',
                'business_bank_accounts.account_name',
                'business_bank_accounts.account_number',
                'business_bank_accounts.swift_code',
                'business_bank_accounts.account_type',
                'system_banks.name as bank_name',
                'system_banks.logo_url as bank_logo'
            )
            ->get()
            ->toArray();

        // If no location-specific accounts, get business-wide accounts
        if (empty($accounts)) {
            $accounts = DB::table('business_bank_accounts')
                ->leftJoin('system_banks', 'business_bank_accounts.bank_id', '=', 'system_banks.id')
                ->where('business_bank_accounts.business_id', $business_id)
                ->whereNull('business_bank_accounts.location_id')
                ->where('business_bank_accounts.is_active', 1)
                ->select(
                    'business_bank_accounts.id',
                    'business_bank_accounts.account_name',
                    'business_bank_accounts.account_number',
                    'business_bank_accounts.swift_code',
                    'business_bank_accounts.account_type',
                    'system_banks.name as bank_name',
                    'system_banks.logo_url as bank_logo'
                )
                ->get()
                ->toArray();
        }

        return array_map(function($account) {
            return (array) $account;
        }, $accounts);
    }

    /**
     * Approve a public payment submission and send SMS notification
     */
    public function approve(Request $request, $submission_id)
    {
        if (!auth()->user()->can('edit_sell')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            // Get submission details
            $submission = DB::table('public_payment_submissions')
                ->where('id', $submission_id)
                ->where('status', 'pending')
                ->first();

            if (!$submission) {
                return response()->json(['success' => false, 'message' => 'Submission not found or already processed']);
            }

            // Get contact and business
            $contact = Contact::find($submission->contact_id);
            $business = \App\Business::find($submission->business_id);

            if (!$contact || !$business) {
                return response()->json(['success' => false, 'message' => 'Contact or business not found']);
            }

            // Update submission status
            DB::table('public_payment_submissions')
                ->where('id', $submission_id)
                ->update([
                    'status' => 'processed',
                    'processed_by' => auth()->id(),
                    'processed_at' => now(),
                    'updated_at' => now()
                ]);

            // Send SMS notification immediately
            $this->sendPaymentApprovedSms($contact, $business, [
                'amount' => $submission->total_amount,
                'method' => 'Bank Transfer',
                'reference' => 'PS-' . str_pad($submission_id, 6, '0', STR_PAD_LEFT),
            ]);

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Payment approved successfully. SMS notification will be sent.'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("Failed to approve payment submission {$submission_id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to approve payment. Please try again.'
            ]);
        }
    }

    /**
     * Send payment approved SMS immediately (not queued)
     */
    private function sendPaymentApprovedSms($contact, $business, $payment_data)
    {
        try {
            // Get notification template
            $template = \App\NotificationTemplate::getTemplate($business->id, 'payment_approved');
            
            if (empty($template['sms_body'])) {
                \Log::warning("No SMS template configured for payment approved for business {$business->id}");
                return;
            }

            // Format mobile number with 960 prefix for Maldivian numbers
            $mobile_number = $contact->mobile;
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

            // Prepare SMS content with template tags
            $sms_content = $this->replacePaymentTemplateTags($template['sms_body'], $contact, $business, $payment_data);

            // Send SMS using existing SMS utility
            $util = new \App\Utils\Util();
            $sms_settings = $business->sms_settings ?: [];
            if (is_string($sms_settings)) {
                $sms_settings = json_decode($sms_settings, true) ?: [];
            }
            
            if (empty($sms_settings) || empty($sms_settings['sms_service'])) {
                \Log::warning("SMS settings not configured for business {$business->id}");
                return;
            }

            // Prepare data array for sendSms method
            $data = [
                'sms_body' => $sms_content,
                'mobile_number' => $mobile_number,
                'sms_settings' => $sms_settings
            ];

            $result = $util->sendSms($data);
            
            \Log::info("Payment approved SMS sent immediately to contact {$contact->id}, mobile: {$mobile_number} (original: {$contact->mobile}), result: " . json_encode($result));
            
        } catch (\Exception $e) {
            \Log::error("Failed to send payment approved SMS to contact {$contact->id}: " . $e->getMessage());
        }
    }

    /**
     * Replace template tags for payment SMS
     */
    private function replacePaymentTemplateTags($content, $contact, $business, $payment_data)
    {
        $tags = [
            '{business_name}' => $business->name,
            '{contact_name}' => $contact->name,
            '{payment_amount}' => number_format($payment_data['amount'], 2),
            '{payment_method}' => $payment_data['method'] ?? 'Bank Transfer',
            '{payment_ref_number}' => $payment_data['reference'] ?? '',
        ];

        // Add contact custom fields
        for ($i = 1; $i <= 10; $i++) {
            $custom_field = 'custom_field' . $i;
            $tags['{contact_custom_field_' . $i . '}'] = $contact->$custom_field ?? '';
        }

        return str_replace(array_keys($tags), array_values($tags), $content);
    }
}