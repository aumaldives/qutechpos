<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\PurchaseController as WebPurchaseController;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends BaseApiController
{
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $purchaseController;

    public function __construct(
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil,
        ModuleUtil $moduleUtil,
        WebPurchaseController $purchaseController
    ) {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->purchaseController = $purchaseController;
    }

    /**
     * Get all purchases
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $location_id = $request->get('location_id');
            $supplier_id = $request->get('supplier_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $status = $request->get('status', 'received'); // received, pending, ordered
            $per_page = $request->get('per_page', 25);

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->with(['contact', 'location', 'purchase_lines.product', 'purchase_lines.variations']);

            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            if ($supplier_id) {
                $query->where('contact_id', $supplier_id);
            }

            if ($start_date) {
                $query->whereDate('transaction_date', '>=', $start_date);
            }

            if ($end_date) {
                $query->whereDate('transaction_date', '<=', $end_date);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $purchases = $query->latest('transaction_date')
                ->paginate($per_page);

            // Transform the data
            $purchases->getCollection()->transform(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'transaction_date' => $purchase->transaction_date,
                    'ref_no' => $purchase->ref_no,
                    'supplier' => $purchase->contact ? [
                        'id' => $purchase->contact->id,
                        'name' => $purchase->contact->name,
                        'business_name' => $purchase->contact->supplier_business_name
                    ] : null,
                    'location' => $purchase->location ? [
                        'id' => $purchase->location->id,
                        'name' => $purchase->location->name
                    ] : null,
                    'status' => $purchase->status,
                    'total_before_tax' => $purchase->total_before_tax,
                    'tax_amount' => $purchase->tax_amount,
                    'final_total' => $purchase->final_total,
                    'payment_status' => $purchase->payment_status,
                    'created_at' => $purchase->created_at,
                    'line_count' => $purchase->purchase_lines->count()
                ];
            });

            return $this->successResponse([
                'purchases' => $purchases->items(),
                'pagination' => [
                    'current_page' => $purchases->currentPage(),
                    'per_page' => $purchases->perPage(),
                    'total' => $purchases->total(),
                    'last_page' => $purchases->lastPage()
                ]
            ], 'Purchases retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve purchases: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new purchase
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'location_id' => 'required|integer',
                'contact_id' => 'required|integer',
                'transaction_date' => 'required|date',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer',
                'products.*.variation_id' => 'required|integer',
                'products.*.purchase_price' => 'required|numeric|min:0',
                'products.*.quantity' => 'required|numeric|min:0.01'
            ]);

            // Use the existing purchase controller logic
            $response = $this->purchaseController->store($request);

            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                DB::commit();
                
                // Get the created purchase ID from session
                $purchase_id = session('purchase_id') ?? $request->get('transaction_id');
                
                return $this->successResponse([
                    'purchase_id' => $purchase_id
                ], 'Purchase created successfully');
            }

            DB::rollBack();
            return $this->errorResponse('Failed to create purchase', 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create purchase: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get specific purchase
     */
    public function show($id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $purchase = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('id', $id)
                ->with([
                    'contact',
                    'location',
                    'purchase_lines.product',
                    'purchase_lines.variations',
                    'purchase_lines.sub_unit',
                    'payment_lines.payment_account'
                ])
                ->first();

            if (!$purchase) {
                return $this->errorResponse('Purchase not found', 404);
            }

            $purchase_data = [
                'id' => $purchase->id,
                'transaction_date' => $purchase->transaction_date,
                'ref_no' => $purchase->ref_no,
                'supplier' => $purchase->contact ? [
                    'id' => $purchase->contact->id,
                    'name' => $purchase->contact->name,
                    'business_name' => $purchase->contact->supplier_business_name,
                    'mobile' => $purchase->contact->mobile,
                    'email' => $purchase->contact->email
                ] : null,
                'location' => $purchase->location ? [
                    'id' => $purchase->location->id,
                    'name' => $purchase->location->name
                ] : null,
                'status' => $purchase->status,
                'purchase_lines' => $purchase->purchase_lines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'product' => [
                            'id' => $line->product->id,
                            'name' => $line->product->name,
                            'sku' => $line->product->sku
                        ],
                        'variation' => $line->variations ? [
                            'id' => $line->variations->id,
                            'name' => $line->variations->name
                        ] : null,
                        'quantity' => $line->quantity,
                        'purchase_price' => $line->purchase_price,
                        'purchase_price_inc_tax' => $line->purchase_price_inc_tax,
                        'line_total' => $line->purchase_price_inc_tax * $line->quantity
                    ];
                }),
                'total_before_tax' => $purchase->total_before_tax,
                'tax_amount' => $purchase->tax_amount,
                'final_total' => $purchase->final_total,
                'payment_status' => $purchase->payment_status,
                'payments' => $purchase->payment_lines->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'method' => $payment->method,
                        'paid_on' => $payment->paid_on,
                        'account' => $payment->payment_account ? $payment->payment_account->name : null
                    ];
                }),
                'additional_notes' => $purchase->additional_notes,
                'created_at' => $purchase->created_at,
                'updated_at' => $purchase->updated_at
            ];

            return $this->successResponse(['purchase' => $purchase_data], 'Purchase retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve purchase: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update purchase
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $business_id = $this->getBusinessId();
            
            $purchase = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('id', $id)
                ->first();

            if (!$purchase) {
                return $this->errorResponse('Purchase not found', 404);
            }

            $request->validate([
                'location_id' => 'sometimes|integer',
                'contact_id' => 'sometimes|integer',
                'transaction_date' => 'sometimes|date',
                'products' => 'sometimes|array|min:1',
                'status' => 'sometimes|in:received,pending,ordered'
            ]);

            // Use existing purchase controller update logic
            $response = $this->purchaseController->update($request, $id);

            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                DB::commit();
                return $this->successResponse(null, 'Purchase updated successfully');
            }

            DB::rollBack();
            return $this->errorResponse('Failed to update purchase', 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update purchase: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete purchase
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $business_id = $this->getBusinessId();
            
            $purchase = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('id', $id)
                ->first();

            if (!$purchase) {
                return $this->errorResponse('Purchase not found', 404);
            }

            // Use existing purchase controller delete logic
            $response = $this->purchaseController->destroy($id);

            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                DB::commit();
                return $this->successResponse(null, 'Purchase deleted successfully');
            }

            DB::rollBack();
            return $this->errorResponse('Failed to delete purchase', 500);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete purchase: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get recent purchases
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $limit = $request->get('limit', 10);
            
            $purchases = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('status', 'received')
                ->with(['contact', 'location'])
                ->latest('transaction_date')
                ->limit($limit)
                ->get();

            // Transform the data
            $purchases = $purchases->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'transaction_date' => $purchase->transaction_date,
                    'ref_no' => $purchase->ref_no,
                    'supplier' => $purchase->contact ? [
                        'id' => $purchase->contact->id,
                        'name' => $purchase->contact->name,
                        'business_name' => $purchase->contact->supplier_business_name
                    ] : null,
                    'location' => $purchase->location ? [
                        'id' => $purchase->location->id,
                        'name' => $purchase->location->name
                    ] : null,
                    'final_total' => (float) $purchase->final_total,
                    'payment_status' => $purchase->payment_status,
                    'created_at' => $purchase->created_at->toISOString()
                ];
            });

            return $this->successResponse([
                'purchases' => $purchases,
                'count' => $purchases->count(),
                'limit' => (int) $limit
            ], 'Recent purchases retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve recent purchases: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get suppliers list
     */
    public function suppliers(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $suppliers = \App\Contact::where('business_id', $business_id)
                ->where('type', 'supplier')
                ->select('id', 'name', 'business_name', 'mobile', 'email')
                ->get();

            return $this->successResponse([
                'suppliers' => $suppliers
            ], 'Suppliers retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve suppliers: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get purchase entry product row
     */
    public function getProductRow(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|integer',
                'variation_id' => 'required|integer'
            ]);

            // Use existing controller method
            $product_row = $this->purchaseController->getPurchaseEntryRow($request);

            return $this->successResponse([
                'product_row' => $product_row
            ], 'Product row retrieved successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get product row: ' . $e->getMessage(), 500);
        }
    }
}