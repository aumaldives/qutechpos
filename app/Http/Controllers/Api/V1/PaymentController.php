<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Transaction;
use App\TransactionPayment;
use App\Events\TransactionPaymentAdded;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends BaseApiController
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Get transaction by invoice number for payment
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvoice(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $validator = Validator::make($request->all(), [
                'invoice_no' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $transaction = Transaction::where('business_id', $business_id)
                ->where('invoice_no', $request->invoice_no)
                ->where('type', 'sell')
                ->with(['contact', 'location'])
                ->first();

            if (!$transaction) {
                return $this->errorResponse('Invoice not found', 404);
            }

            // Calculate payment information
            $total_paid = TransactionPayment::where('transaction_id', $transaction->id)
                ->sum('amount');
            
            $due_amount = $transaction->final_total - $total_paid;

            $invoice_data = [
                'id' => $transaction->id,
                'invoice_no' => $transaction->invoice_no,
                'transaction_date' => $transaction->transaction_date,
                'total_amount' => (float) $transaction->final_total,
                'total_paid' => (float) $total_paid,
                'due_amount' => (float) $due_amount,
                'payment_status' => $transaction->payment_status,
                'customer' => [
                    'id' => $transaction->contact_id,
                    'name' => $transaction->contact ? $transaction->contact->name : 'Walk-in Customer',
                    'mobile' => $transaction->contact ? $transaction->contact->mobile : null,
                ],
                'location' => [
                    'id' => $transaction->location_id,
                    'name' => $transaction->location ? $transaction->location->name : null,
                ]
            ];

            return $this->successResponse(
                $invoice_data,
                'Invoice details retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve invoice: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add payment to transaction by invoice number
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPayment(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $validator = Validator::make($request->all(), [
                'invoice_no' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string|in:cash,card,bank_transfer,cheque,other',
                'payment_note' => 'nullable|string|max:500',
                'payment_ref_no' => 'nullable|string|max:255',
                'payment_date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $transaction = Transaction::where('business_id', $business_id)
                ->where('invoice_no', $request->invoice_no)
                ->where('type', 'sell')
                ->first();

            if (!$transaction) {
                return $this->errorResponse('Invoice not found', 404);
            }

            // Calculate current due amount
            $total_paid = TransactionPayment::where('transaction_id', $transaction->id)
                ->sum('amount');
            
            $due_amount = $transaction->final_total - $total_paid;

            if ($request->amount > $due_amount) {
                return $this->errorResponse(
                    'Payment amount (' . number_format($request->amount, 2) . ') cannot exceed due amount (' . number_format($due_amount, 2) . ')',
                    422
                );
            }

            DB::beginTransaction();

            // Create payment record
            $payment_data = [
                'transaction_id' => $transaction->id,
                'business_id' => $business_id,
                'amount' => $request->amount,
                'method' => $request->payment_method,
                'payment_ref_no' => $request->payment_ref_no,
                'note' => $request->payment_note,
                'paid_on' => $request->payment_date ?? now()->format('Y-m-d H:i:s'),
                'created_by' => auth()->id() ?? 1,
                'payment_for' => $transaction->contact_id,
                'payment_status' => 'paid'
            ];

            $payment = TransactionPayment::create($payment_data);

            // Update transaction payment status
            $new_total_paid = $total_paid + $request->amount;
            $remaining_due = $transaction->final_total - $new_total_paid;

            if ($remaining_due <= 0) {
                $transaction->payment_status = 'paid';
            } else {
                $transaction->payment_status = 'partial';
            }
            
            $transaction->save();

            // Fire payment added event
            event(new TransactionPaymentAdded($payment));

            DB::commit();

            $response_data = [
                'payment_id' => $payment->id,
                'transaction_id' => $transaction->id,
                'invoice_no' => $transaction->invoice_no,
                'amount_paid' => (float) $request->amount,
                'total_paid' => (float) $new_total_paid,
                'remaining_due' => (float) $remaining_due,
                'payment_status' => $transaction->payment_status,
                'payment_ref_no' => $payment->payment_ref_no,
                'paid_on' => $payment->paid_on
            ];

            return $this->successResponse(
                $response_data,
                'Payment added successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to add payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get payment history for a transaction by invoice number
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $validator = Validator::make($request->all(), [
                'invoice_no' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $transaction = Transaction::where('business_id', $business_id)
                ->where('invoice_no', $request->invoice_no)
                ->where('type', 'sell')
                ->first();

            if (!$transaction) {
                return $this->errorResponse('Invoice not found', 404);
            }

            $payments = TransactionPayment::where('transaction_id', $transaction->id)
                ->with('created_user')
                ->orderBy('created_at', 'desc')
                ->get();

            $payment_history = $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->method,
                    'payment_ref_no' => $payment->payment_ref_no,
                    'note' => $payment->note,
                    'paid_on' => $payment->paid_on,
                    'created_by' => $payment->created_user ? $payment->created_user->first_name . ' ' . $payment->created_user->last_name : 'System',
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $total_paid = $payments->sum('amount');
            $due_amount = $transaction->final_total - $total_paid;

            $response_data = [
                'invoice_no' => $transaction->invoice_no,
                'total_amount' => (float) $transaction->final_total,
                'total_paid' => (float) $total_paid,
                'due_amount' => (float) $due_amount,
                'payment_status' => $transaction->payment_status,
                'payments' => $payment_history
            ];

            return $this->successResponse(
                $response_data,
                'Payment history retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve payment history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get list of invoices with payment status
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvoices(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $params = $this->getPaginationParams($request);
            
            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->with(['contact']);

            // Apply filters
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('customer_id')) {
                $query->where('contact_id', $request->customer_id);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_no', 'LIKE', "%{$search}%")
                      ->orWhereHas('contact', function($cq) use ($search) {
                          $cq->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            $query->orderBy('created_at', 'desc');

            $transactions = $query->paginate($params['per_page']);

            // Transform the data
            $transactions->getCollection()->transform(function ($transaction) {
                $total_paid = TransactionPayment::where('transaction_id', $transaction->id)
                    ->sum('amount');
                
                $due_amount = $transaction->final_total - $total_paid;

                return [
                    'id' => $transaction->id,
                    'invoice_no' => $transaction->invoice_no,
                    'transaction_date' => $transaction->transaction_date,
                    'total_amount' => (float) $transaction->final_total,
                    'total_paid' => (float) $total_paid,
                    'due_amount' => (float) $due_amount,
                    'payment_status' => $transaction->payment_status,
                    'customer' => [
                        'id' => $transaction->contact_id,
                        'name' => $transaction->contact ? $transaction->contact->name : 'Walk-in Customer',
                    ],
                ];
            });

            return $this->paginatedResponse($transactions, 'Invoices retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve invoices: ' . $e->getMessage(), 500);
        }
    }
}