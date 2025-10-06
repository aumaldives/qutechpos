<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\TransactionResource;
use App\Transaction;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TransactionController extends BaseApiController
{
    /**
     * Display a listing of transactions
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $params = $this->getPaginationParams($request);
            
            $query = Transaction::where('business_id', $business_id)
                ->with(['contact', 'location', 'sales_person']);
            
            // Apply filters
            $searchableFields = ['ref_no', 'invoice_no', 'additional_notes', 'staff_note'];
            $query = $this->applyFilters($query, $request, $searchableFields);
            
            // Transaction type filter
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            
            // Status filter
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Payment status filter
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }
            
            // Location filter
            if ($request->has('location_id')) {
                $query->where('location_id', $request->location_id);
            }
            
            // Contact filter
            if ($request->has('contact_id')) {
                $query->where('contact_id', $request->contact_id);
            }
            
            // Date range filters
            if ($request->has('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }
            
            // Amount range filters
            if ($request->has('min_amount')) {
                $query->where('final_total', '>=', $request->min_amount);
            }
            
            if ($request->has('max_amount')) {
                $query->where('final_total', '<=', $request->max_amount);
            }
            
            // Load payment totals
            $query->withSum('payment_lines', 'amount');
            
            $transactions = $query->orderBy('transaction_date', 'desc')
                ->paginate($params['per_page']);

            // Use resource collection for consistent output
            $transactions->getCollection()->transform(function ($transaction) {
                return new TransactionResource($transaction);
            });

            return $this->successResponse([
                'data' => $transactions->items(),
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'from' => $transactions->firstItem(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'to' => $transactions->lastItem(),
                    'total' => $transactions->total(),
                ]
            ], 'Transactions retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve transactions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created transaction
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // NOTE: Full transaction creation with line items requires complex business logic
        // This method provides basic transaction scaffolding for API consistency
        return $this->errorResponse('Full transaction creation with line items is complex and requires the TransactionUtil. Use the web interface for complete transaction creation. This endpoint is reserved for future implementation.', 501);
    }

    /**
     * Display the specified transaction
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $query = Transaction::where('business_id', $business_id)
                ->where('id', $id)
                ->with([
                    'contact', 
                    'location', 
                    'sales_person',
                    'tax',
                    'sell_lines.product',
                    'sell_lines.variation',
                    'purchase_lines.product', 
                    'purchase_lines.variation',
                    'payment_lines'
                ])
                ->withSum('payment_lines', 'amount');

            $transaction = $query->first();

            if (!$transaction) {
                return $this->errorResponse('Transaction not found', 404);
            }

            return $this->resourceResponse(
                new TransactionResource($transaction),
                'Transaction retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified transaction
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $transaction = Transaction::where('business_id', $business_id)->find($id);
            
            if (!$transaction) {
                return $this->errorResponse('Transaction not found', 404);
            }
            
            // Check if transaction can be updated (only draft and pending transactions)
            if (!in_array($transaction->status, ['draft', 'pending'])) {
                return $this->errorResponse('Only draft and pending transactions can be updated', 422);
            }

            $validator = Validator::make($request->all(), [
                'contact_id' => 'sometimes|integer|exists:contacts,id',
                'transaction_date' => 'sometimes|date',
                'ref_no' => 'sometimes|string|max:255',
                'status' => 'sometimes|in:draft,pending,ordered,received,final',
                'staff_note' => 'nullable|string',
                'additional_notes' => 'nullable|string',
                'discount_amount' => 'nullable|numeric|min:0',
                'discount_type' => 'nullable|in:fixed,percentage',
                'tax_id' => 'nullable|integer|exists:tax_rates,id',
                'is_tax_inclusive' => 'nullable|boolean',
                'shipping_charges' => 'nullable|numeric|min:0',
                'shipping_details' => 'nullable|string',
                'payment_status' => 'sometimes|in:paid,partial,due',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            DB::beginTransaction();

            // Update basic transaction fields
            $updateData = $validator->validated();
            $transaction->update($updateData);

            DB::commit();

            // Reload transaction with relationships
            $transaction->load([
                'contact', 
                'location', 
                'sales_person',
                'sell_lines.product',
                'sell_lines.variation',
                'purchase_lines.product', 
                'purchase_lines.variation',
                'payment_lines'
            ]);

            return $this->resourceResponse(
                new TransactionResource($transaction),
                'Transaction updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified transaction
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $transaction = Transaction::where('business_id', $business_id)->find($id);
            
            if (!$transaction) {
                return $this->errorResponse('Transaction not found', 404);
            }
            
            // Check if transaction can be deleted (only draft transactions)
            if ($transaction->status !== 'draft') {
                return $this->errorResponse('Only draft transactions can be deleted. Consider voiding instead.', 422);
            }
            
            // Check if transaction has payments
            if ($transaction->payment_lines()->count() > 0) {
                return $this->errorResponse('Cannot delete transaction that has payments. Please remove payments first.', 422);
            }

            DB::beginTransaction();

            // Delete related records first
            if ($transaction->type === 'sell') {
                $transaction->sell_lines()->delete();
            } elseif ($transaction->type === 'purchase') {
                $transaction->purchase_lines()->delete();
            }
            
            // Delete the transaction
            $transaction->delete();

            DB::commit();

            return $this->successResponse(null, 'Transaction deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add payment to transaction
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPayment(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $transaction = Transaction::where('business_id', $business_id)->find($id);
            
            if (!$transaction) {
                return $this->errorResponse('Transaction not found', 404);
            }
            
            // Validate payment data
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'method' => 'required|in:cash,card,cheque,bank_transfer,other',
                'paid_on' => 'nullable|date',
                'account_id' => 'nullable|integer|exists:accounts,id',
                'cheque_number' => 'nullable|string|max:255',
                'card_type' => 'nullable|in:credit,debit',
                'card_number' => 'nullable|string|max:20',
                'card_transaction_number' => 'nullable|string|max:255',
                'note' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            DB::beginTransaction();

            // Calculate remaining balance
            $totalPaid = $transaction->payment_lines->sum('amount');
            $remainingBalance = $transaction->final_total - $totalPaid;
            
            if ($request->amount > $remainingBalance) {
                return $this->errorResponse('Payment amount exceeds remaining balance of ' . number_format($remainingBalance, 2), 422);
            }

            // Create payment record
            $paymentData = $validator->validated();
            $paymentData['transaction_id'] = $transaction->id;
            $paymentData['business_id'] = $business_id;
            $paymentData['created_by'] = auth()->id() ?? 1;
            $paymentData['paid_on'] = $paymentData['paid_on'] ?? now()->toDateString();

            $payment = \App\TransactionPayment::create($paymentData);

            // Update transaction payment status
            $newTotalPaid = $totalPaid + $request->amount;
            if ($newTotalPaid >= $transaction->final_total) {
                $transaction->payment_status = 'paid';
            } elseif ($newTotalPaid > 0) {
                $transaction->payment_status = 'partial';
            } else {
                $transaction->payment_status = 'due';
            }
            $transaction->save();

            DB::commit();

            return $this->successResponse([
                'payment' => [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->method,
                    'paid_on' => $payment->paid_on,
                    'note' => $payment->note,
                    'created_at' => $payment->created_at?->toISOString(),
                ],
                'transaction' => [
                    'id' => $transaction->id,
                    'payment_status' => $transaction->payment_status,
                    'total_paid' => (float) $newTotalPaid,
                    'balance_due' => (float) ($transaction->final_total - $newTotalPaid),
                ]
            ], 'Payment added successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to add payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get transaction payments
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function payments($id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $transaction = Transaction::where('business_id', $business_id)->find($id);
            
            if (!$transaction) {
                return $this->errorResponse('Transaction not found', 404);
            }

            $payments = $transaction->payment_lines()
                ->with(['created_user', 'payment_account'])
                ->orderBy('paid_on', 'desc')
                ->get();

            $paymentsData = $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->method,
                    'paid_on' => $payment->paid_on,
                    'account' => $payment->payment_account ? [
                        'id' => $payment->payment_account->id,
                        'name' => $payment->payment_account->name,
                    ] : null,
                    'cheque_number' => $payment->cheque_number,
                    'card_type' => $payment->card_type,
                    'card_number' => $payment->card_number ? '****' . substr($payment->card_number, -4) : null,
                    'card_transaction_number' => $payment->card_transaction_number,
                    'note' => $payment->note,
                    'created_by' => $payment->created_user ? [
                        'id' => $payment->created_user->id,
                        'name' => $payment->created_user->first_name . ' ' . $payment->created_user->last_name,
                    ] : null,
                    'created_at' => $payment->created_at?->toISOString(),
                ];
            });

            return $this->successResponse([
                'transaction' => [
                    'id' => $transaction->id,
                    'ref_no' => $transaction->ref_no,
                    'final_total' => (float) $transaction->final_total,
                    'payment_status' => $transaction->payment_status,
                ],
                'payments' => $paymentsData,
                'summary' => [
                    'total_payments' => $payments->count(),
                    'total_paid' => (float) $payments->sum('amount'),
                    'balance_due' => (float) ($transaction->final_total - $payments->sum('amount')),
                ]
            ], 'Transaction payments retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve payments: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get sales transactions
     */
    public function sales(Request $request): JsonResponse
    {
        $request->merge(['type' => 'sell']);
        return $this->index($request);
    }

    /**
     * Get purchases transactions
     */
    public function purchases(Request $request): JsonResponse
    {
        $request->merge(['type' => 'purchase']);
        return $this->index($request);
    }

    /**
     * Create a new sale
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSale(Request $request): JsonResponse
    {
        // NOTE: Full sale creation with products, variations, stock adjustments, etc.
        // requires the TransactionUtil and complex business logic validation
        return $this->errorResponse('Full sale creation requires complex business logic with stock management. Use POS interface or web interface for complete sales. This endpoint is reserved for future implementation.', 501);
    }

    /**
     * Create a new purchase
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPurchase(Request $request): JsonResponse
    {
        // NOTE: Full purchase creation with products, variations, stock adjustments, etc.
        // requires the TransactionUtil and complex business logic validation  
        return $this->errorResponse('Full purchase creation requires complex business logic with stock management. Use the web interface for complete purchases. This endpoint is reserved for future implementation.', 501);
    }

    /**
     * Get recent sales
     */
    public function recentSales(Request $request): JsonResponse
    {
        $request->merge(['type' => 'sell']);
        return $this->index($request);
    }

    /**
     * Get recent purchases
     */
    public function recentPurchases(Request $request): JsonResponse
    {
        $request->merge(['type' => 'purchase']);
        return $this->index($request);
    }
}