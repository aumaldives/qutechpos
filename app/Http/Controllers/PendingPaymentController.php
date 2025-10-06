<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\BusinessUtil;
use App\Utils\TransactionUtil;
use Yajra\DataTables\Facades\DataTables;
use DB;

class PendingPaymentController extends Controller
{
    /**
     * All Utils
     *
     * @var BusinessUtil
     */
    protected $businessUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param BusinessUtil $businessUtil
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, TransactionUtil $transactionUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display pending payments list
     */
    public function index()
    {
        if (!auth()->user()->can('access_pending_payments')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            // Handle both single invoice payments and public multi-invoice payments
            $single_payments = DB::table('pending_bank_payments as pbp')
                ->join('transactions as t', 'pbp.transaction_id', '=', 't.id')
                ->join('business_bank_accounts as bba', 'pbp.bank_account_id', '=', 'bba.id')
                ->join('system_banks as sb', 'bba.bank_id', '=', 'sb.id')
                ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftJoin('users as u', 'pbp.processed_by', '=', 'u.id')
                ->where('pbp.business_id', $business_id)
                ->select([
                    'pbp.id',
                    'pbp.amount',
                    'pbp.receipt_file_path',
                    'c.name as customer_name',
                    'c.email as customer_email',
                    'c.mobile as customer_mobile',
                    'pbp.status',
                    'pbp.submitted_at',
                    'pbp.processed_at',
                    DB::raw('NULL as rejection_reason'),
                    't.invoice_no',
                    't.final_total',
                    'bba.account_name',
                    'bba.account_number',
                    'sb.name as bank_name',
                    'sb.logo_url as bank_logo',
                    'c.name as contact_customer_name',
                    'u.first_name as processed_by_name',
                    DB::raw('"single" as payment_type'),
                    DB::raw('1 as invoice_count')
                ]);

            // Multi-invoice payments from public portal
            $multi_payments = DB::table('public_payment_submissions as pps')
                ->leftJoin('contacts as c', 'pps.contact_id', '=', 'c.id')
                ->leftJoin('users as u', 'pps.processed_by', '=', 'u.id')
                ->leftJoin('business_bank_accounts as bba', 'pps.bank_account_id', '=', 'bba.id')
                ->leftJoin('system_banks as sb', 'bba.bank_id', '=', 'sb.id')
                ->leftJoin(
                    DB::raw('(SELECT payment_submission_id, COUNT(*) as invoice_count FROM public_payment_invoice_mappings GROUP BY payment_submission_id) as inv_count'),
                    'pps.id', '=', 'inv_count.payment_submission_id'
                )
                ->where('pps.business_id', $business_id)
                ->select([
                    'pps.id',
                    'pps.total_amount as amount',
                    'pps.receipt_file_path',
                    'pps.customer_name',
                    'pps.customer_email',
                    'pps.customer_mobile',
                    'pps.status',
                    'pps.submitted_at',
                    'pps.processed_at',
                    'pps.rejection_reason',
                    DB::raw('CONCAT("Multi-Invoice Payment (", COALESCE(inv_count.invoice_count, 0), " invoices)") as invoice_no'),
                    'pps.total_amount as final_total',
                    'bba.account_name',
                    'bba.account_number',
                    'sb.name as bank_name',
                    'sb.logo_url as bank_logo',
                    DB::raw('COALESCE(c.name, pps.customer_name) as contact_customer_name'),
                    'u.first_name as processed_by_name',
                    DB::raw('"multi" as payment_type'),
                    DB::raw('COALESCE(inv_count.invoice_count, 0) as invoice_count')
                ]);

            // Filter by status if provided
            if (request()->has('status') && request()->status != '') {
                $single_payments->where('pbp.status', request()->status);
                $multi_payments->where('pps.status', request()->status);
            }

            // Combine both payment types - can't use orderBy directly with union
            $pending_payments = DB::query()->fromSub(
                $single_payments->unionAll($multi_payments), 
                'combined_payments'
            )->orderBy('submitted_at', 'desc');

            return DataTables::of($pending_payments)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group" style="display: flex; justify-content: center; gap: 5px;">';
                    
                    if ($row->status == 'pending') {
                        if ($row->payment_type == 'multi') {
                            $html .= '<button class="btn btn-xs btn-success approve-multi-payment" data-id="' . $row->id . '" title="Approve Multi-Invoice Payment"><i class="fa fa-check"></i></button>';
                            $html .= '<button class="btn btn-xs btn-danger reject-multi-payment" data-id="' . $row->id . '" title="Reject Multi-Invoice Payment"><i class="fa fa-times"></i></button>';
                        } else {
                            $html .= '<button class="btn btn-xs btn-success approve-payment" data-id="' . $row->id . '" title="Approve"><i class="fa fa-check"></i></button>';
                            $html .= '<button class="btn btn-xs btn-danger reject-payment" data-id="' . $row->id . '" title="Reject"><i class="fa fa-times"></i></button>';
                        }
                    }
                    
                    if ($row->payment_type == 'multi') {
                        $html .= '<button class="btn btn-xs btn-info view-multi-details" data-id="' . $row->id . '" title="View Multi-Invoice Details"><i class="fa fa-eye"></i></button>';
                    } else {
                        $html .= '<button class="btn btn-xs btn-info view-details" data-id="' . $row->id . '" title="View Details"><i class="fa fa-eye"></i></button>';
                    }
                    
                    if ($row->receipt_file_path) {
                        // Build the correct URL based on the file path
                        $receiptUrl = '';
                        if (str_starts_with($row->receipt_file_path, 'uploads/')) {
                            $receiptUrl = asset($row->receipt_file_path);
                        } elseif (str_starts_with($row->receipt_file_path, 'public_payments/')) {
                            $receiptUrl = asset($row->receipt_file_path);
                        } else {
                            $receiptUrl = asset('storage/' . $row->receipt_file_path);
                        }
                        $html .= '<button class="btn btn-xs btn-primary view-receipt-image" data-image="' . $receiptUrl . '" title="View Receipt"><i class="fa fa-image"></i></button>';
                    }
                    
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('bank_info', function ($row) {
                    $html = '<div class="text-center" style="display: flex; justify-content: center; align-items: center;">';
                    if ($row->bank_logo) {
                        $html .= '<img src="' . $row->bank_logo . '" alt="' . $row->bank_name . '" style="width: 40px; height: 40px; object-fit: contain; margin: 0 auto;">';
                    } else {
                        $html .= '<div style="width: 40px; height: 40px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 4px; color: #6c757d; font-size: 1.5rem; margin: 0 auto;"><i class="fas fa-university"></i></div>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('invoice_info', function ($row) {
                    $html = '<strong>' . $row->invoice_no . '</strong><br><small>Total: ' . number_format($row->final_total, 2) . '</small>';
                    if ($row->payment_type == 'multi') {
                        $html .= '<br><small class="text-info"><i class="fa fa-files-o"></i> Multi-Invoice</small>';
                    }
                    return $html;
                })
                ->addColumn('amount_formatted', function ($row) {
                    return '<strong>' . number_format($row->amount, 2) . '</strong>';
                })
                ->addColumn('status_formatted', function ($row) {
                    $status_colors = [
                        'pending' => 'warning',
                        'approved' => 'info', 
                        'processed' => 'success',
                        'rejected' => 'danger'
                    ];
                    $color = $status_colors[$row->status] ?? 'default';
                    return '<span class="label label-' . $color . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('submitted_formatted', function ($row) {
                    return \Carbon\Carbon::parse($row->submitted_at)->format('M j, Y g:i A');
                })
                ->rawColumns(['action', 'bank_info', 'invoice_info', 'amount_formatted', 'status_formatted', 'submitted_formatted'])
                ->make(true);
        }

        return view('pending_payments.index');
    }

    /**
     * Get payment details
     */
    public function show($id)
    {
        if (!auth()->user()->can('access_pending_payments')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $payment = DB::table('pending_bank_payments as pbp')
            ->join('transactions as t', 'pbp.transaction_id', '=', 't.id')
            ->join('business_bank_accounts as bba', 'pbp.bank_account_id', '=', 'bba.id')
            ->join('system_banks as sb', 'bba.bank_id', '=', 'sb.id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('users as processed_user', 'pbp.processed_by', '=', 'processed_user.id')
            ->where('pbp.id', $id)
            ->where('pbp.business_id', $business_id)
            ->select([
                'pbp.*',
                't.invoice_no',
                't.final_total',
                't.transaction_date',
                'bba.account_name',
                'bba.account_number',
                'bba.account_type',
                'bba.swift_code',
                'sb.name as bank_name',
                'sb.logo_url as bank_logo',
                'sb.full_name as bank_full_name',
                'c.name as contact_customer_name',
                'c.email as customer_email_db',
                'c.mobile as customer_mobile_db',
                'processed_user.first_name as processed_by_name'
            ])
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'msg' => 'Payment not found']);
        }

        return response()->json(['success' => true, 'payment' => $payment]);
    }

    /**
     * Approve payment and create transaction payment
     */
    public function approve(Request $request, $id)
    {
        if (!auth()->user()->can('approve_pending_payments')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');
            $user_id = auth()->user()->id;

            // Get payment details
            $payment = DB::table('pending_bank_payments as pbp')
                ->join('transactions as t', 'pbp.transaction_id', '=', 't.id')
                ->where('pbp.id', $id)
                ->where('pbp.business_id', $business_id)
                ->where('pbp.status', 'pending')
                ->select('pbp.*', 't.*', 't.id as transaction_id', 'pbp.id as payment_id')
                ->first();

            if (!$payment) {
                return response()->json(['success' => false, 'msg' => 'Payment not found or already processed']);
            }

            // Create transaction payment using the same logic as Add Payment
            $payment_data = [
                'transaction_id' => $payment->transaction_id,
                'business_id' => $business_id,
                'amount' => $payment->amount,
                'method' => 'bank_transfer',
                'paid_on' => now()->format('Y-m-d H:i:s'),
                'created_by' => $user_id,
                'payment_for' => $payment->contact_id,
                'note' => 'Bank transfer payment - Ref: Pending Payment #' . $payment->payment_id,
                'document' => $payment->receipt_file_path
            ];

            $transaction_payment_id = DB::table('transaction_payments')->insertGetId([
                'transaction_id' => $payment_data['transaction_id'],
                'business_id' => $payment_data['business_id'],
                'amount' => $payment_data['amount'],
                'method' => $payment_data['method'],
                'transaction_no' => null,
                'card_number' => null,
                'card_holder_name' => null,
                'card_transaction_number' => null,
                'card_type' => null,
                'card_month' => null,
                'card_year' => null,
                'card_security' => null,
                'cheque_number' => null,
                'bank_account_number' => null,
                'paid_on' => $payment_data['paid_on'],
                'created_by' => $payment_data['created_by'],
                'payment_for' => $payment_data['payment_for'],
                'parent_id' => null,
                'note' => $payment_data['note'],
                'document' => $payment_data['document'],
                'is_return' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update pending payment status
            DB::table('pending_bank_payments')
                ->where('id', $id)
                ->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'processed_by' => $user_id,
                    'payment_id' => $transaction_payment_id,
                    'updated_at' => now()
                ]);

            // Update transaction payment status
            $this->transactionUtil->updatePaymentStatus($payment->transaction_id, null);

            // Send payment approval SMS for single invoice
            $this->sendSinglePaymentApprovedSms($payment, $business_id);

            DB::commit();

            return response()->json([
                'success' => true, 
                'msg' => 'Payment approved and processed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error approving bank payment: ' . $e->getMessage(), [
                'payment_id' => $id,
                'user_id' => auth()->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false, 
                'msg' => 'Failed to process payment approval'
            ]);
        }
    }

    /**
     * Reject payment
     */
    public function reject(Request $request, $id)
    {
        if (!auth()->user()->can('approve_pending_payments')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = auth()->user()->id;

            $updated = DB::table('pending_bank_payments')
                ->where('id', $id)
                ->where('business_id', $business_id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'processed_at' => now(),
                    'processed_by' => $user_id,
                    'rejection_reason' => $request->rejection_reason,
                    'updated_at' => now()
                ]);

            if ($updated) {
                return response()->json([
                    'success' => true, 
                    'msg' => 'Payment rejected successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false, 
                    'msg' => 'Payment not found or already processed'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'msg' => 'Failed to reject payment'
            ]);
        }
    }

    /**
     * Get multi-invoice payment details
     */
    public function showMultiPayment($id)
    {
        if (!auth()->user()->can('access_pending_payments')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $payment = DB::table('public_payment_submissions as pps')
            ->leftJoin('contacts as c', 'pps.contact_id', '=', 'c.id')
            ->leftJoin('business_bank_accounts as bba', 'pps.bank_account_id', '=', 'bba.id')
            ->leftJoin('system_banks as sb', 'bba.bank_id', '=', 'sb.id')
            ->leftJoin('users as processed_user', 'pps.processed_by', '=', 'processed_user.id')
            ->where('pps.id', $id)
            ->where('pps.business_id', $business_id)
            ->select([
                'pps.*',
                'c.name as contact_name',
                'c.email as contact_email',
                'c.mobile as contact_mobile',
                'bba.account_name',
                'bba.account_number',
                'sb.name as bank_name',
                'sb.logo_url as bank_logo',
                'processed_user.first_name as processed_by_name'
            ])
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'msg' => 'Payment not found']);
        }

        // Get invoice allocations
        $allocations = DB::table('public_payment_invoice_mappings as ppim')
            ->join('transactions as t', 'ppim.transaction_id', '=', 't.id')
            ->where('ppim.payment_submission_id', $id)
            ->select([
                'ppim.applied_amount',
                't.id as transaction_id',
                't.invoice_no',
                't.final_total',
                DB::raw('(SELECT COALESCE(SUM(tp.amount), 0) FROM transaction_payments as tp WHERE tp.transaction_id=t.id) as total_paid'),
                't.transaction_date',
                DB::raw('(t.final_total - (SELECT COALESCE(SUM(tp.amount), 0) FROM transaction_payments as tp WHERE tp.transaction_id=t.id)) as due_amount')
            ])
            ->orderBy('t.transaction_date', 'asc')
            ->get();

        // Get invoice numbers as a comma-separated string
        $invoice_numbers = $allocations->pluck('invoice_no')->implode(', ');
        
        // Add invoice numbers to payment object
        $payment->invoice_numbers = $invoice_numbers;
        
        return response()->json([
            'success' => true, 
            'payment' => $payment,
            'allocations' => $allocations
        ]);
    }

    /**
     * Approve multi-invoice payment
     */
    public function approveMultiPayment(Request $request, $id)
    {
        if (!auth()->user()->can('approve_pending_payments')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');
            $user_id = auth()->user()->id;

            // Get payment details
            $payment = DB::table('public_payment_submissions')
                ->where('id', $id)
                ->where('business_id', $business_id)
                ->where('status', 'pending')
                ->first();

            if (!$payment) {
                return response()->json(['success' => false, 'msg' => 'Payment not found or already processed']);
            }

            // Get invoice mappings
            $mappings = DB::table('public_payment_invoice_mappings')
                ->where('payment_submission_id', $id)
                ->get();

            // Create individual transaction payments for each invoice
            foreach ($mappings as $mapping) {
                $transaction_payment_id = DB::table('transaction_payments')->insertGetId([
                    'transaction_id' => $mapping->transaction_id,
                    'business_id' => $business_id,
                    'amount' => $mapping->applied_amount,
                    'method' => 'bank_transfer',
                    'paid_on' => now()->format('Y-m-d H:i:s'),
                    'created_by' => $user_id,
                    'payment_for' => $payment->contact_id,
                    'note' => 'Multi-Invoice Payment - Ref: #' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                    'document' => $payment->receipt_file_path,
                    'is_return' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Update transaction payment status
                $this->transactionUtil->updatePaymentStatus($mapping->transaction_id, null);
            }

            // Update payment submission status
            DB::table('public_payment_submissions')
                ->where('id', $id)
                ->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'processed_by' => $user_id,
                    'updated_at' => now()
                ]);

            // Send payment approval SMS
            $this->sendPaymentApprovedSms($payment, $business_id);

            DB::commit();

            return response()->json([
                'success' => true, 
                'msg' => 'Multi-invoice payment approved and processed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error approving multi-invoice payment: ' . $e->getMessage(), [
                'payment_id' => $id,
                'user_id' => auth()->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false, 
                'msg' => 'Failed to process payment approval'
            ]);
        }
    }

    /**
     * Reject multi-invoice payment
     */
    public function rejectMultiPayment(Request $request, $id)
    {
        if (!auth()->user()->can('approve_pending_payments')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = auth()->user()->id;

            $updated = DB::table('public_payment_submissions')
                ->where('id', $id)
                ->where('business_id', $business_id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'processed_at' => now(),
                    'processed_by' => $user_id,
                    'rejection_reason' => $request->rejection_reason,
                    'updated_at' => now()
                ]);

            if ($updated) {
                return response()->json([
                    'success' => true, 
                    'msg' => 'Multi-invoice payment rejected successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false, 
                    'msg' => 'Payment not found or already processed'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'msg' => 'Failed to reject multi-invoice payment'
            ]);
        }
    }

    /**
     * Send payment approved SMS immediately
     */
    private function sendPaymentApprovedSms($payment, $business_id)
    {
        try {
            // Get contact and business
            $contact = \App\Contact::find($payment->contact_id);
            $business = \App\Business::find($business_id);
            
            if (!$contact || !$business) {
                \Log::warning("Contact or business not found for SMS - Contact: {$payment->contact_id}, Business: {$business_id}");
                return;
            }

            // Get notification template
            $template = \App\NotificationTemplate::getTemplate($business_id, 'payment_approved');
            
            if (empty($template['sms_body'])) {
                \Log::warning("No SMS template configured for payment approved for business {$business_id}");
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
            $payment_data = [
                'amount' => $payment->total_amount,
                'method' => 'Bank Transfer',
                'reference' => 'PS-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            ];
            
            $sms_content = $this->replacePaymentTemplateTags($template['sms_body'], $contact, $business, $payment_data);

            // Send SMS using existing SMS utility
            $util = new \App\Utils\Util();
            $sms_settings = $business->sms_settings ?: [];
            if (is_string($sms_settings)) {
                $sms_settings = json_decode($sms_settings, true) ?: [];
            }
            
            if (empty($sms_settings) || empty($sms_settings['sms_service'])) {
                \Log::warning("SMS settings not configured for business {$business_id}");
                return;
            }

            // Prepare data array for sendSms method
            $data = [
                'sms_body' => $sms_content,
                'mobile_number' => $mobile_number,
                'sms_settings' => $sms_settings
            ];

            $result = $util->sendSms($data);
            
            \Log::info("Multi-payment approved SMS sent to contact {$contact->id}, mobile: {$mobile_number} (original: {$contact->mobile}), result: " . json_encode($result));
            
        } catch (\Exception $e) {
            \Log::error("Failed to send multi-payment approved SMS to contact {$payment->contact_id}: " . $e->getMessage());
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

    /**
     * Send payment approved SMS for single invoice payments
     */
    private function sendSinglePaymentApprovedSms($payment, $business_id)
    {
        try {
            // Get contact and business
            $contact = \App\Contact::find($payment->contact_id);
            $business = \App\Business::find($business_id);
            
            if (!$contact || !$business) {
                \Log::warning("Contact or business not found for single payment SMS - Contact: {$payment->contact_id}, Business: {$business_id}");
                return;
            }

            // Get notification template
            $template = \App\NotificationTemplate::getTemplate($business_id, 'payment_approved');
            
            if (empty($template['sms_body'])) {
                \Log::warning("No SMS template configured for payment approved for business {$business_id}");
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
            $payment_data = [
                'amount' => $payment->amount,
                'method' => 'Bank Transfer',
                'reference' => 'BP-' . str_pad($payment->payment_id, 6, '0', STR_PAD_LEFT),
            ];
            
            $sms_content = $this->replacePaymentTemplateTags($template['sms_body'], $contact, $business, $payment_data);

            // Send SMS using existing SMS utility
            $util = new \App\Utils\Util();
            $sms_settings = $business->sms_settings ?: [];
            if (is_string($sms_settings)) {
                $sms_settings = json_decode($sms_settings, true) ?: [];
            }
            
            if (empty($sms_settings) || empty($sms_settings['sms_service'])) {
                \Log::warning("SMS settings not configured for business {$business_id}");
                return;
            }

            // Prepare data array for sendSms method
            $data = [
                'sms_body' => $sms_content,
                'mobile_number' => $mobile_number,
                'sms_settings' => $sms_settings
            ];

            $result = $util->sendSms($data);
            
            \Log::info("Single payment approved SMS sent to contact {$contact->id}, mobile: {$mobile_number} (original: {$contact->mobile}), result: " . json_encode($result));
            
        } catch (\Exception $e) {
            \Log::error("Failed to send single payment approved SMS to contact {$payment->contact_id}: " . $e->getMessage());
        }
    }
}
