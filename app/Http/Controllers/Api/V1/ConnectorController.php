<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Transaction;
use App\PurchaseLine;
use App\TransactionPayment;
use App\Contact;
use App\Product;
use App\Variation;
use App\BusinessLocation;
use App\Utils\TransactionUtil;
use App\Utils\ProductUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConnectorController extends BaseApiController
{
    protected $transactionUtil;
    protected $productUtil;

    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Create a purchase transaction directly without using web controller
     */
    public function createPurchase(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validate basic required fields
            $request->validate([
                'location_id' => 'required|integer',
                'contact_id' => 'required|integer',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer',
                'products.*.quantity' => 'required|numeric|min:0.01',
                'products.*.purchase_price' => 'required|numeric|min:0'
            ]);

            $business_id = $this->getBusinessId();
            $data = $request->all();

            // Verify location and supplier exist
            $location = BusinessLocation::where('business_id', $business_id)
                ->where('id', $data['location_id'])
                ->first();

            if (!$location) {
                return $this->errorResponse('Invalid location_id', 422);
            }

            $contact = Contact::where('business_id', $business_id)
                ->where('id', $data['contact_id'])
                ->first();

            if (!$contact) {
                return $this->errorResponse('Invalid contact_id', 422);
            }

            // Calculate totals
            $total_before_tax = 0;
            $final_total = 0;

            foreach ($data['products'] as $product) {
                $line_total = $product['quantity'] * $product['purchase_price'];
                $total_before_tax += $line_total;
                $final_total += $line_total;
            }

            // Create purchase transaction
            $transaction = Transaction::create([
                'business_id' => $business_id,
                'type' => 'purchase',
                'status' => $data['status'] ?? 'received',
                'contact_id' => $data['contact_id'],
                'transaction_date' => now(),
                'ref_no' => $data['ref_no'] ?? 'PUR-' . time(),
                'location_id' => $data['location_id'],
                'total_before_tax' => $total_before_tax,
                'tax_amount' => 0,
                'final_total' => $final_total,
                'payment_status' => 'paid',
                'additional_notes' => $data['additional_notes'] ?? '',
                'created_by' => 99 // API system user
            ]);

            // Create purchase lines
            foreach ($data['products'] as $productData) {
                // Verify product exists
                $product = Product::where('business_id', $business_id)
                    ->where('id', $productData['product_id'])
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    return $this->errorResponse("Product with ID {$productData['product_id']} not found", 422);
                }

                // Get or create variation
                $variation_id = $productData['variation_id'] ?? $productData['product_id'];

                PurchaseLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $productData['product_id'],
                    'variation_id' => $variation_id,
                    'quantity' => $productData['quantity'],
                    'purchase_price' => $productData['purchase_price'],
                    'purchase_price_inc_tax' => $productData['purchase_price'],
                    'product_unit_id' => $productData['product_unit_id'] ?? null,
                    'sub_unit_id' => $productData['sub_unit_id'] ?? null
                ]);

                // Update product stock
                $this->productUtil->updateProductQuantity(
                    $data['location_id'],
                    $productData['product_id'],
                    $variation_id,
                    $productData['quantity'],
                    0,
                    'purchase',
                    false
                );
            }

            // Create payment record if total > 0
            if ($final_total > 0) {
                TransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'business_id' => $business_id,
                    'amount' => $final_total,
                    'method' => 'cash',
                    'paid_on' => now(),
                    'created_by' => 99 // API user
                ]);
            }

            DB::commit();

            return $this->successResponse([
                'id' => $transaction->id,
                'ref_no' => $transaction->ref_no,
                'location_id' => $transaction->location_id,
                'total' => $transaction->final_total,
                'status' => $transaction->status
            ], 'Purchase created successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create purchase: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Simplified USDT stock addition
     * Predefined for USDT product (ID: 373) to USDT SWAP location (ID: 503)
     */
    public function addUSDTStock(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|numeric|min:0.01',
                'cost_per_unit' => 'required|numeric|min:0'
            ]);

            $quantity = $request->input('quantity');
            $costPerUnit = $request->input('cost_per_unit');

            // Create purchase request for USDT
            $purchaseData = [
                'location_id' => 503, // USDT SWAP
                'contact_id' => $request->input('supplier_id', 16), // Default supplier
                'ref_no' => $request->input('ref_no', 'USDT-STOCK-' . time()),
                'status' => 'received',
                'additional_notes' => $request->input('notes', 'USDT stock added via API'),
                'products' => [
                    [
                        'product_id' => 373, // USDT product
                        'variation_id' => 373, // Same as product ID
                        'quantity' => $quantity,
                        'purchase_price' => $costPerUnit,
                        'product_unit_id' => 880, // USDT unit
                        'sub_unit_id' => 880
                    ]
                ]
            ];

            // Create a new request with this data
            $newRequest = new Request($purchaseData);

            return $this->createPurchase($newRequest);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add USDT stock: ' . $e->getMessage(), 500);
        }
    }
}