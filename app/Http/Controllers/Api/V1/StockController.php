<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends BaseApiController
{
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $stockAdjustmentController;
    protected $stockTransferController;

    public function __construct(
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil,
        ModuleUtil $moduleUtil,
        StockAdjustmentController $stockAdjustmentController,
        StockTransferController $stockTransferController
    ) {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->stockAdjustmentController = $stockAdjustmentController;
        $this->stockTransferController = $stockTransferController;
    }

    /**
     * Get stock adjustments
     */
    public function adjustments(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $per_page = $request->get('per_page', 25);

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'stock_adjustment')
                ->with(['location', 'stock_adjustment_lines.product', 'stock_adjustment_lines.variations']);

            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            if ($start_date) {
                $query->whereDate('transaction_date', '>=', $start_date);
            }

            if ($end_date) {
                $query->whereDate('transaction_date', '<=', $end_date);
            }

            $adjustments = $query->latest('transaction_date')
                ->paginate($per_page);

            // Transform the data
            $adjustments->getCollection()->transform(function ($adjustment) {
                return [
                    'id' => $adjustment->id,
                    'transaction_date' => $adjustment->transaction_date,
                    'ref_no' => $adjustment->ref_no,
                    'location' => $adjustment->location ? [
                        'id' => $adjustment->location->id,
                        'name' => $adjustment->location->name
                    ] : null,
                    'adjustment_type' => $adjustment->adjustment_type,
                    'total_amount_recovered' => $adjustment->total_amount_recovered,
                    'final_total' => $adjustment->final_total,
                    'additional_notes' => $adjustment->additional_notes,
                    'created_at' => $adjustment->created_at,
                    'line_count' => $adjustment->stock_adjustment_lines->count()
                ];
            });

            return $this->sendSuccess('Stock adjustments retrieved successfully', [
                'adjustments' => $adjustments->items(),
                'pagination' => [
                    'current_page' => $adjustments->currentPage(),
                    'per_page' => $adjustments->perPage(),
                    'total' => $adjustments->total(),
                    'last_page' => $adjustments->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve stock adjustments', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create stock adjustment
     */
    public function createAdjustment(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'location_id' => 'required|integer',
                'transaction_date' => 'required|date',
                'adjustment_type' => 'required|in:normal,abnormal',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer',
                'products.*.variation_id' => 'required|integer',
                'products.*.quantity' => 'required|numeric',
                'products.*.unit_price' => 'sometimes|numeric|min:0'
            ]);

            // Use the existing stock adjustment controller logic
            $response = $this->stockAdjustmentController->store($request);

            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                DB::commit();
                
                $adjustment_id = session('adjustment_id') ?? $request->get('transaction_id');
                
                return $this->sendSuccess('Stock adjustment created successfully', [
                    'adjustment_id' => $adjustment_id
                ]);
            }

            DB::rollBack();
            return $this->sendError('Failed to create stock adjustment');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->sendError('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to create stock adjustment', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get stock transfers
     */
    public function transfers(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $status = $request->get('status'); // pending, in_transit, completed
            $per_page = $request->get('per_page', 25);

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'stock_transfer')
                ->with(['location', 'transfer_location', 'stock_adjustment_lines.product', 'stock_adjustment_lines.variations']);

            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($start_date) {
                $query->whereDate('transaction_date', '>=', $start_date);
            }

            if ($end_date) {
                $query->whereDate('transaction_date', '<=', $end_date);
            }

            $transfers = $query->latest('transaction_date')
                ->paginate($per_page);

            // Transform the data
            $transfers->getCollection()->transform(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'transaction_date' => $transfer->transaction_date,
                    'ref_no' => $transfer->ref_no,
                    'from_location' => $transfer->location ? [
                        'id' => $transfer->location->id,
                        'name' => $transfer->location->name
                    ] : null,
                    'to_location' => $transfer->transfer_location ? [
                        'id' => $transfer->transfer_location->id,
                        'name' => $transfer->transfer_location->name
                    ] : null,
                    'status' => $transfer->status,
                    'shipping_charges' => $transfer->shipping_charges,
                    'final_total' => $transfer->final_total,
                    'additional_notes' => $transfer->additional_notes,
                    'created_at' => $transfer->created_at,
                    'line_count' => $transfer->stock_adjustment_lines->count()
                ];
            });

            return $this->sendSuccess('Stock transfers retrieved successfully', [
                'transfers' => $transfers->items(),
                'pagination' => [
                    'current_page' => $transfers->currentPage(),
                    'per_page' => $transfers->perPage(),
                    'total' => $transfers->total(),
                    'last_page' => $transfers->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve stock transfers', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create stock transfer
     */
    public function createTransfer(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'location_id' => 'required|integer',
                'transfer_location_id' => 'required|integer|different:location_id',
                'transaction_date' => 'required|date',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer',
                'products.*.variation_id' => 'required|integer',
                'products.*.quantity' => 'required|numeric|min:0.01'
            ]);

            // Use the existing stock transfer controller logic
            $response = $this->stockTransferController->store($request);

            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                DB::commit();
                
                $transfer_id = session('transfer_id') ?? $request->get('transaction_id');
                
                return $this->sendSuccess('Stock transfer created successfully', [
                    'transfer_id' => $transfer_id
                ]);
            }

            DB::rollBack();
            return $this->sendError('Failed to create stock transfer');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->sendError('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to create stock transfer', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update stock transfer status
     */
    public function updateTransferStatus(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $business_id = auth()->user()->business_id;
            
            $transfer = Transaction::where('business_id', $business_id)
                ->where('type', 'stock_transfer')
                ->where('id', $id)
                ->first();

            if (!$transfer) {
                return $this->sendError('Stock transfer not found', [], 404);
            }

            $request->validate([
                'status' => 'required|in:pending,in_transit,completed'
            ]);

            // Use existing stock transfer controller update status logic
            $response = $this->stockTransferController->updateStatus($request, $id);

            if ($response instanceof \Illuminate\Http\JsonResponse || $response instanceof \Illuminate\Http\RedirectResponse) {
                DB::commit();
                return $this->sendSuccess('Stock transfer status updated successfully');
            }

            DB::rollBack();
            return $this->sendError('Failed to update stock transfer status');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->sendError('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to update stock transfer status', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get stock levels by location
     */
    public function levels(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            $location_id = $request->get('location_id');
            $product_id = $request->get('product_id');
            $category_id = $request->get('category_id');
            $low_stock_only = $request->get('low_stock_only', false);
            $per_page = $request->get('per_page', 25);

            if (!$location_id) {
                return $this->sendError('Location ID is required', [], 422);
            }

            // Get stock details by querying variation_location_details directly
            $query = \App\VariationLocationDetails::join('variations', 'variation_location_details.variation_id', '=', 'variations.id')
                ->join('products', 'variations.product_id', '=', 'products.id')
                ->where('products.business_id', $business_id)
                ->where('variation_location_details.location_id', $location_id);

            if ($product_id) {
                $query->where('products.id', $product_id);
            }

            if ($category_id) {
                $query->where('products.category_id', $category_id);
            }

            $stock_details = $query->select(
                'variation_location_details.*',
                'products.name as product_name',
                'products.id as product_id',
                'variations.name as variation_name',
                'variations.sub_sku as sku'
            )->get();

            if ($low_stock_only) {
                $stock_details = $stock_details->filter(function ($item) {
                    return $item->qty_available <= ($item->alert_quantity ?? 0);
                });
            }

            // Paginate results
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $per_page;
            $total = $stock_details->count();
            $items = $stock_details->slice($offset, $per_page)->values();

            $stock_data = $items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'variation_id' => $item->variation_id,
                    'product_name' => $item->product_name ?? $item->name,
                    'variation_name' => $item->variation_name,
                    'sku' => $item->sku,
                    'qty_available' => $item->qty_available,
                    'alert_quantity' => $item->alert_quantity,
                    'is_low_stock' => $item->qty_available <= ($item->alert_quantity ?? 0),
                    'unit_price' => $item->unit_price ?? 0,
                    'total_value' => ($item->qty_available ?? 0) * ($item->unit_price ?? 0)
                ];
            });

            return $this->sendSuccess('Stock levels retrieved successfully', [
                'stock_levels' => $stock_data,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => $per_page,
                    'total' => $total,
                    'last_page' => ceil($total / $per_page)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve stock levels', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get opening stock
     */
    public function openingStock(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            $location_id = $request->get('location_id');
            $product_id = $request->get('product_id');
            
            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'opening_stock')
                ->with(['location', 'purchase_lines.product', 'purchase_lines.variations']);

            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            $opening_stock = $query->get();

            $stock_data = $opening_stock->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'ref_no' => $stock->ref_no,
                    'location' => $stock->location ? [
                        'id' => $stock->location->id,
                        'name' => $stock->location->name
                    ] : null,
                    'products' => $stock->purchase_lines->map(function ($line) {
                        return [
                            'product_id' => $line->product->id,
                            'product_name' => $line->product->name,
                            'variation_id' => $line->variations->id ?? null,
                            'variation_name' => $line->variations->name ?? null,
                            'quantity' => $line->quantity,
                            'purchase_price' => $line->purchase_price,
                            'total_value' => $line->quantity * $line->purchase_price
                        ];
                    }),
                    'total_amount' => $stock->final_total,
                    'created_at' => $stock->created_at
                ];
            });

            return $this->sendSuccess('Opening stock retrieved successfully', [
                'opening_stock' => $stock_data
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve opening stock', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get stock movement history
     */
    public function movements(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            $product_id = $request->get('product_id');
            $variation_id = $request->get('variation_id');
            $location_id = $request->get('location_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $per_page = $request->get('per_page', 25);

            if (!$product_id || !$variation_id || !$location_id) {
                return $this->sendError('Product ID, Variation ID, and Location ID are required', [], 422);
            }

            $movements = $this->productUtil->getVariationStockHistory($business_id, $product_id, $variation_id, $location_id, $start_date, $end_date);

            // Paginate results
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $per_page;
            $total = count($movements);
            $items = array_slice($movements, $offset, $per_page);

            return $this->sendSuccess('Stock movements retrieved successfully', [
                'movements' => $items,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => $per_page,
                    'total' => $total,
                    'last_page' => ceil($total / $per_page)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve stock movements', ['error' => $e->getMessage()]);
        }
    }
}