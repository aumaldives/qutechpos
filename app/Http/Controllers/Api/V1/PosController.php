<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\SellPosController;
use App\PlasticBagType;
use App\PlasticBagUsage;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends BaseApiController
{
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $posController;

    public function __construct(
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil,
        ModuleUtil $moduleUtil,
        SellPosController $posController
    ) {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->posController = $posController;
    }

    /**
     * Get available plastic bag types for POS
     */
    public function getPlasticBagTypes(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            // Validate location_id parameter
            $request->validate([
                'location_id' => 'required|integer'
            ]);
            
            $location_id = $request->get('location_id');

            $plastic_bags = PlasticBagType::forBusiness($business_id)
                ->active()
                ->select(['id', 'name', 'price', 'stock_quantity', 'description'])
                ->get();

            $plastic_bag_data = $plastic_bags->map(function ($bag) use ($location_id) {
                return [
                    'id' => $bag->id,
                    'name' => $bag->name,
                    'price' => (float) $bag->price,
                    'stock_quantity' => (float) $bag->stock_quantity,
                    'description' => $bag->description,
                    'in_stock' => $bag->stock_quantity > 0,
                    'location_id' => $location_id // Include location_id in response
                ];
            });

            return $this->successResponse([
                'location_id' => $location_id,
                'plastic_bags' => $plastic_bag_data
            ], 'Plastic bag types retrieved successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get plastic bag types', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get product suggestions for POS
     */
    public function getProductSuggestions(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $location_id = $request->get('location_id');
            $term = $request->get('term', '');
            $category_id = $request->get('category_id');
            $brand_id = $request->get('brand_id');

            if (!$location_id) {
                return $this->errorResponse('Location ID is required', 422);
            }

            // Search products with variations for POS
            $products = $this->searchProductsForPOS($business_id, $term, $location_id, $category_id, $brand_id);

            return $this->successResponse([
                'products' => $products
            ], 'Product suggestions retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get product suggestions', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get product row data for POS
     */
    public function getProductRow(Request $request): JsonResponse
    {
        try {
            $variation_id = $request->get('variation_id');
            $location_id = $request->get('location_id');

            if (!$variation_id || !$location_id) {
                return $this->errorResponse('Variation ID and Location ID are required', 422);
            }

            // Get product row data
            $product_data = $this->posController->getProductRow($variation_id, $location_id);

            return $this->successResponse([
                'product_data' => $product_data
            ], 'Product row data retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get product row data', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create POS sale
     */
    public function createSale(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Auto-assign walk-in customer if no contact provided
            if (empty($request->contact_id)) {
                $walk_in_customer = $this->getWalkInCustomer();
                $request->merge(['contact_id' => $walk_in_customer->id]);
            }

            // Validate required fields
            $request->validate([
                'location_id' => 'required|integer',
                'contact_id' => 'required|integer',
                'products' => 'required|array|min:1',
                'products.*.variation_id' => 'required_without:products.*.sku|integer',
                'products.*.sku' => 'required_without:products.*.variation_id|string',
                'products.*.quantity' => 'required|numeric|min:0.01',
                'products.*.unit_price' => 'sometimes|numeric|min:0',
                'payment' => 'required|array',
                'payment.*.method' => 'required|string',
                'payment.*.amount' => 'required|numeric|min:0',
                // Plastic bags validation
                'plastic_bags' => 'sometimes|array',
                'plastic_bags.*.type_id' => 'required_with:plastic_bags|integer',
                'plastic_bags.*.quantity' => 'required_with:plastic_bags|numeric|min:1',
                // Additional features validation
                'is_credit_sale' => 'sometimes|boolean',
                'discount_amount' => 'sometimes|numeric|min:0',
                'discount_type' => 'sometimes|string|in:fixed,percentage',
                'tax_id' => 'sometimes|integer',
                'shipping_charges' => 'sometimes|numeric|min:0',
                'commission_agent' => 'sometimes|integer',
                'sale_note' => 'sometimes|string|max:1000',
                'staff_note' => 'sometimes|string|max:1000'
            ]);

            // Process products - resolve SKUs to variation IDs if needed
            $this->processProductSKUs($request);
            
            // Process additional API-specific features before creating sale
            $this->processApiSpecificFeatures($request);
            
            // Transform products array to match SellPosController expectations
            $this->transformProductsForPosController($request);
            
            // Transform plastic bags for SellPosController
            $this->transformPlasticBagsForPosController($request);

            // CRITICAL: Temporarily authenticate as API user for permission checks in SellPosController
            $api_user = $request->attributes->get('api_user');
            $previous_user = \Auth::user(); // Store current auth state
            
            if ($api_user) {
                \Auth::login($api_user);
                
                // Start session for API request if not already started
                if (!$request->hasSession()) {
                    $request->setLaravelSession(app('session')->driver());
                }
                
                // Set up session data that SellPosController expects
                $business = $api_user->business;
                $request->session()->put('user.business_id', $business->id);
                $request->session()->put('user.id', $api_user->id);
                
                // Set up business object properly for session
                $business_data = [
                    'id' => $business->id,
                    'accounting_method' => $business->accounting_method ?? 'fifo',
                    'enable_rp' => $business->enable_rp ?? 0,
                    'sales_cmsn_agnt' => $business->sales_cmsn_agnt ?? 0,
                    'enable_category' => $business->enable_category ?? 1,
                    'enable_brand' => $business->enable_brand ?? 1,
                    'enabled_modules' => is_string($business->enabled_modules) 
                        ? json_decode($business->enabled_modules, true) 
                        : ($business->enabled_modules ?? []),
                    // Critical: Add date/time format for uf_date function
                    'date_format' => $business->date_format ?? 'd/m/Y',
                    'time_format' => $business->time_format ?? '24',
                    'time_zone' => $business->time_zone ?? 'UTC',
                    // Product expiry settings
                    'enable_product_expiry' => $business->enable_product_expiry ?? 0,
                    'on_product_expiry' => $business->on_product_expiry ?? 'keep_selling'
                ];
                
                $request->session()->put('business', $business_data);
                
                // Also set individual keys for backward compatibility
                foreach ($business_data as $key => $value) {
                    $request->session()->put("business.{$key}", $value);
                }
            }

            // Debug logging before POS controller call
            \Log::info('About to call POS Controller', [
                'authenticated_user_id' => auth()->id(),
                'business_id' => $this->getBusinessId(),
                'request_method' => $request->method(),
                'request_data_keys' => array_keys($request->all()),
                'products_count' => count($request->input('products', [])),
                'location_id' => $request->input('location_id'),
                'contact_id' => $request->input('contact_id'),
            ]);

            // Create the POS sale using existing controller logic
            $response = $this->posController->store($request);
            
            // Restore previous auth state and clean up session
            if ($previous_user) {
                \Auth::login($previous_user);
            } else {
                \Auth::logout();
            }
            
            // Clean up temporary session data
            if ($api_user) {
                $request->session()->forget([
                    'user.business_id', 'user.id', 'business',
                    'business.accounting_method', 'business.enable_rp', 'business.sales_cmsn_agnt',
                    'business.enable_category', 'business.enable_brand', 'business.enabled_modules',
                    'business.date_format', 'business.time_format', 'business.time_zone', 'business.enable_product_expiry', 'business.on_product_expiry'
                ]);
            }

            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                // Extract transaction_id from the session status message or flash data
                $status_data = session('status');
                $transaction_id = null;
                $invoice_url = null;
                
                if ($status_data && isset($status_data['receipt'])) {
                    // Try to extract transaction ID from receipt data
                    if (isset($status_data['receipt']['id'])) {
                        $transaction_id = $status_data['receipt']['id'];
                    }
                    if (isset($status_data['receipt']['html_content'])) {
                        // Extract invoice URL if available
                        preg_match('/invoice\/(\d+)/', $status_data['receipt']['html_content'], $matches);
                        if (isset($matches[1])) {
                            $invoice_url = url('/invoice/' . $matches[1]);
                        }
                    }
                }
                
                // Fallback: Get the latest transaction for this user/business as transaction ID
                if (!$transaction_id) {
                    $latest_transaction = \App\Transaction::where('business_id', $this->getBusinessId())
                        ->where('created_by', auth()->id())
                        ->where('type', 'sell')
                        ->latest()
                        ->first();
                    if ($latest_transaction) {
                        $transaction_id = $latest_transaction->id;
                        $invoice_url = url('/invoice/' . $transaction_id);
                    }
                }
                
                // Plastic bags are now processed by SellPosController automatically
                // No need to process them separately here
                
                DB::commit();
                
                return $this->successResponse([
                    'transaction_id' => $transaction_id,
                    'invoice_url' => $invoice_url,
                    'plastic_bags_included' => $request->has('pos_plastic_bag_data')
                ], 'Sale created successfully');
            }

            DB::rollBack();
            return $this->errorResponse('Failed to create sale', 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Detailed error logging for debugging
            \Log::error('POS Sale Creation Failed', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['api_key']), // Don't log API key
                'business_id' => $this->getBusinessId(),
                'user_id' => auth()->id(),
            ]);
            
            return $this->errorResponse('Failed to create sale', 500, [
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
                'debug_info' => 'Check logs for detailed error information'
            ]);
        }
    }

    /**
     * Get recent POS transactions
     */
    public function getRecentTransactions(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $user_id = auth()->id() ?? 1;
            $status = $request->get('status', 'final');
            $limit = $request->get('limit', 10);
            $location_id = $request->get('location_id'); // Optional location filter

            $query = \App\Transaction::where('business_id', $business_id)
                ->where('created_by', $user_id)
                ->where('type', 'sell')
                ->where('is_direct_sale', 0);
            
            // Filter by location if specified
            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            if ($status == 'quotation') {
                $query->where('status', 'draft')
                    ->where('sub_status', 'quotation');
            } elseif ($status == 'draft') {
                $query->where('status', 'draft')
                    ->whereNull('sub_status');
            } else {
                $query->where('status', $status);
            }

            $transactions = $query->orderBy('created_at', 'desc')
                ->with(['contact', 'location'])
                ->limit($limit)
                ->get();

            $transaction_data = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'invoice_no' => $transaction->invoice_no,
                    'transaction_date' => $transaction->transaction_date,
                    'final_total' => $transaction->final_total,
                    'payment_status' => $transaction->payment_status,
                    'status' => $transaction->status,
                    'contact' => $transaction->contact ? [
                        'id' => $transaction->contact->id,
                        'name' => $transaction->contact->name,
                        'business_name' => $transaction->contact->business_name
                    ] : null,
                    'location' => $transaction->location ? [
                        'id' => $transaction->location->id,
                        'name' => $transaction->location->name
                    ] : null,
                    'created_at' => $transaction->created_at
                ];
            });

            return $this->successResponse([
                'transactions' => $transaction_data
            ], 'Recent transactions retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get recent transactions', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get POS settings and configuration
     */
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $location_id = $request->get('location_id');

            // Get basic business info
            $business = \App\Business::find($business_id);
            $location = $location_id ? \App\BusinessLocation::find($location_id) : null;
            
            // Get basic payment methods
            $payment_methods = [
                ['id' => 'cash', 'name' => 'Cash', 'type' => 'cash'],
                ['id' => 'card', 'name' => 'Card', 'type' => 'card'],
                ['id' => 'bank_transfer', 'name' => 'Bank Transfer', 'type' => 'bank_transfer'],
                ['id' => 'other', 'name' => 'Other', 'type' => 'other']
            ];
            
            // Get tax rates
            $tax_rates = \App\TaxRate::where('business_id', $business_id)
                ->where('is_tax_group', 0)
                ->select(['id', 'name', 'amount', 'is_tax_group'])
                ->get();

            $settings = [
                'business' => $business ? [
                    'id' => $business->id,
                    'name' => $business->name,
                    'currency_symbol' => $business->currency->symbol ?? '$',
                    'currency_code' => $business->currency->code ?? 'USD',
                ] : null,
                'location' => $location ? [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->city . ', ' . $location->state
                ] : null,
                'payment_methods' => $payment_methods,
                'tax_rates' => $tax_rates->map(function($tax) {
                    return [
                        'id' => $tax->id,
                        'name' => $tax->name,
                        'rate' => (float) $tax->amount
                    ];
                })
            ];

            return $this->successResponse($settings, 'POS settings retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get POS settings', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Save sale as draft
     */
    public function saveDraft(Request $request): JsonResponse
    {
        try {
            \DB::beginTransaction();
            
            $business_id = $this->getBusinessId();
            
            $request->validate([
                'location_id' => 'required', // Can be integer (table ID) or string (location code like BL0003)
                'products' => 'required|array|min:1',
                'products.*.variation_id' => 'required_without:products.*.sku|integer',
                'products.*.sku' => 'required_without:products.*.variation_id|string',
                'products.*.quantity' => 'required|numeric|min:0.01',
                'products.*.unit_price' => 'sometimes|numeric|min:0'
            ]);

            // SECURITY: Validate location belongs to user's business
            // Accept both location_id (like BL0003) and table row id for backwards compatibility
            $location = \App\BusinessLocation::where('business_id', $business_id)
                ->where(function($query) use ($request) {
                    $query->where('location_id', $request->location_id)
                          ->orWhere('id', $request->location_id);
                })
                ->first();
                
            if (!$location) {
                return $this->errorResponse('Invalid location_id: Location does not belong to your business or does not exist', 403);
            }
            
            // API key authentication: Check if user exists and has location permissions
            $user = auth()->user();
            if ($user) {
                // Full user context available - check permitted locations
                $permitted_locations = $user->permitted_locations();
                if ($permitted_locations != 'all' && !in_array($request->location_id, $permitted_locations)) {
                    return $this->errorResponse('Access denied: You do not have permission to access this location', 403);
                }
            }
            // For API key auth without user context, we only validate business ownership (already done above)

            // Process products - resolve SKUs to variation IDs if needed (same as main sale)
            $this->processProductSKUs($request);
            
            // Transform products array to proper format (same as main sale)
            $this->transformProductsForPosController($request);
            
            // Calculate totals properly
            $products = $request->input('products', []);
            $product_total = 0;
            foreach ($products as $product) {
                $product_total += $product['quantity'] * $product['unit_price_inc_tax'];
            }

            // Generate automatic reference number like web system (use table ID for internal operations)
            $invoice_no = app(\App\Utils\TransactionUtil::class)->getInvoiceNumber($business_id, 'draft', $location->id);

            // Create draft transaction with proper totals
            $draft = \App\Transaction::create([
                'business_id' => $business_id,
                'location_id' => $location->id, // Use the actual table ID for foreign key
                'contact_id' => $request->contact_id,
                'type' => 'sell',
                'status' => 'draft',
                'sub_status' => null, // Regular draft (not quotation)
                'invoice_no' => $invoice_no,
                'transaction_date' => now(), // Use current timestamp directly for MySQL
                'total_before_tax' => $product_total,
                'tax_amount' => 0, // Calculate later if needed
                'final_total' => $product_total,
                'created_by' => auth()->id() ?? 1,
                'additional_notes' => $request->input('notes', '')
            ]);

            // Create transaction lines (this is what was missing!)
            foreach ($products as $product) {
                \App\TransactionSellLine::create([
                    'transaction_id' => $draft->id,
                    'product_id' => $product['product_id'],
                    'variation_id' => $product['variation_id'],
                    'quantity' => $product['quantity'],
                    'unit_price_before_discount' => $product['unit_price_exc_tax'],
                    'unit_price' => $product['unit_price_exc_tax'],
                    'line_discount_type' => $product['line_discount_type'] ?? 'fixed',
                    'line_discount_amount' => $product['line_discount_amount'] ?? 0,
                    'unit_price_inc_tax' => $product['unit_price_inc_tax'],
                    'tax_id' => $product['tax_id'],
                    'item_tax' => $product['item_tax'] ?? 0
                ]);
            }
            
            \DB::commit();

            return $this->successResponse([
                'draft_id' => $draft->id,
                'invoice_no' => $draft->invoice_no,
                'location_id' => $draft->location_id,
                'contact_id' => $draft->contact_id,
                'final_total' => $draft->final_total,
                'product_count' => count($products)
            ], 'Draft saved successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \DB::rollBack();
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->errorResponse('Failed to save draft', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get saved drafts
     */
    public function getDrafts(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $status = $request->get('status', 'draft'); // draft or quotation
            
            $query = \App\Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'draft')
                ->where('is_direct_sale', 0);
                
            if ($status == 'quotation') {
                $query->where('sub_status', 'quotation');
            } else {
                $query->where(function($q) {
                    $q->whereNull('sub_status')
                      ->orWhere('sub_status', 'pos_draft');
                });
            }
            
            $drafts = $query->with(['contact', 'location', 'sell_lines'])
                ->select('id', 'invoice_no', 'transaction_date', 'contact_id', 'location_id', 'sub_status', 'additional_notes', 'created_at', 'final_total')
                ->orderBy('created_at', 'desc')
                ->get();
                
            $draft_data = $drafts->map(function($draft) {
                // Try to get data from transaction lines first (new format), then fallback to JSON (old format)
                $product_count = $draft->sell_lines ? $draft->sell_lines->count() : 0;
                $notes = json_decode($draft->additional_notes, true) ?? [];
                
                // If no transaction lines but JSON has products, use old format count
                if ($product_count == 0 && isset($notes['products'])) {
                    $product_count = count($notes['products']);
                }
                
                return [
                    'draft_id' => $draft->id,
                    'invoice_no' => $draft->invoice_no,
                    'location_id' => $draft->location_id,
                    'location_name' => $draft->location ? $draft->location->name : null,
                    'contact_id' => $draft->contact_id,
                    'contact_name' => $draft->contact ? $draft->contact->name : null,
                    'product_count' => $product_count,
                    'final_total' => (float) $draft->final_total,
                    'quotation_id' => $notes['quotation_id'] ?? null,
                    'reference' => $notes['reference'] ?? null,
                    'notes' => is_string($draft->additional_notes) && !empty($draft->additional_notes) && $draft->additional_notes !== '[]' 
                        ? ($notes['draft_notes'] ?? $draft->additional_notes) 
                        : null,
                    'created_at' => $draft->created_at
                ];
            });

            return $this->successResponse([
                'drafts' => $draft_data
            ], 'Drafts retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get drafts', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Load specific draft
     */
    public function loadDraft($draft_id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $draft = \App\Transaction::where('business_id', $business_id)
                ->where('id', $draft_id)
                ->where('type', 'sell')
                ->where('status', 'draft')
                ->where('is_direct_sale', 0)
                ->with(['contact', 'location', 'sell_lines.product', 'sell_lines.variation'])
                ->first();

            if (!$draft) {
                return $this->errorResponse('Draft not found', 404);
            }

            // Load products from transaction lines (new format) or fallback to JSON (old format)
            $products = [];
            if ($draft->sell_lines && $draft->sell_lines->count() > 0) {
                // New format: load from transaction lines
                $products = $draft->sell_lines->map(function($line) {
                    return [
                        'variation_id' => $line->variation_id,
                        'product_id' => $line->product_id,
                        'product_name' => $line->product->name,
                        'variation_name' => $line->variation ? $line->variation->name : null,
                        'quantity' => (float) $line->quantity,
                        'unit_price' => (float) $line->unit_price_inc_tax,
                        'unit_price_inc_tax' => (float) $line->unit_price_inc_tax,
                        'unit_price_exc_tax' => (float) $line->unit_price,
                        'line_total' => (float) ($line->quantity * $line->unit_price_inc_tax)
                    ];
                })->toArray();
            } else {
                // Old format: try to load from JSON
                $notes = json_decode($draft->additional_notes, true) ?? [];
                $products = $notes['products'] ?? [];
            }
            
            $notes = json_decode($draft->additional_notes, true) ?? [];
            
            $draft_data = [
                'draft_id' => $draft->id,
                'invoice_no' => $draft->invoice_no,
                'location_id' => $draft->location_id,
                'location_name' => $draft->location ? $draft->location->name : null,
                'contact_id' => $draft->contact_id,
                'contact_name' => $draft->contact ? $draft->contact->name : null,
                'products' => $products,
                'product_count' => count($products),
                'final_total' => (float) $draft->final_total,
                'quotation_id' => $notes['quotation_id'] ?? null,
                'reference' => $notes['reference'] ?? null,
                'notes' => is_string($draft->additional_notes) && !empty($draft->additional_notes) && $draft->additional_notes !== '[]' 
                    ? ($notes['draft_notes'] ?? $draft->additional_notes) 
                    : null,
                'transaction_date' => $draft->transaction_date,
                'created_at' => $draft->created_at
            ];

            return $this->successResponse([
                'draft' => $draft_data
            ], 'Draft loaded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load draft', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete draft
     */
    public function deleteDraft($draft_id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $draft = \App\Transaction::where('business_id', $business_id)
                ->where('id', $draft_id)
                ->where('type', 'sell')
                ->where('status', 'draft')
                ->where('is_direct_sale', 0)
                ->first();

            if (!$draft) {
                return $this->errorResponse('Draft not found', 404);
            }

            $draft->delete();

            return $this->successResponse(null, 'Draft deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete draft', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get business information and locations (Read-Only)
     */
    public function getBusinessInfo(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            // Get business details
            $business = \App\Business::find($business_id);
            if (!$business) {
                return $this->errorResponse('Business not found', 404);
            }
            
            // Get all business locations
            $locations = \App\BusinessLocation::where('business_id', $business_id)
                ->select(['id', 'location_id', 'name', 'landmark', 'city', 'state', 'country', 'zip_code', 'is_active'])
                ->orderBy('name')
                ->get();
                
            // Get business settings
            $currency = $business->currency;
            $financial_year = $business->fy_start_month;
            
            $business_info = [
                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'owner_name' => $business->owner->first_name . ' ' . $business->owner->last_name,
                    'email' => $business->email,
                    'website' => $business->website,
                    'logo' => $business->logo ? url('uploads/business_logos/' . $business->logo) : null,
                    'time_zone' => $business->time_zone ?? 'UTC',
                    'currency' => [
                        'symbol' => $currency->symbol ?? '$',
                        'code' => $currency->code ?? 'USD',
                        'symbol_placement' => $currency->symbol_placement ?? 'before',
                        'thousand_separator' => $currency->thousand_separator ?? ',',
                        'decimal_separator' => $currency->decimal_separator ?? '.'
                    ],
                    'financial_year_start' => $financial_year,
                    'created_at' => $business->created_at,
                ],
                'locations' => $locations->map(function($location) {
                    return [
                        'location_id' => $location->location_id ?? $location->id, // Use location_id if available, fallback to id
                        'table_id' => $location->id, // Keep table ID for internal reference
                        'name' => $location->name,
                        'address' => trim(implode(', ', array_filter([
                            $location->landmark,
                            $location->city,
                            $location->state,
                            $location->country
                        ]))),
                        'zip_code' => $location->zip_code,
                        'is_active' => (bool)$location->is_active
                    ];
                })
            ];
            
            return $this->successResponse($business_info, 'Business information retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get business information', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Transform products array to match SellPosController expectations
     */
    private function transformProductsForPosController(Request $request)
    {
        $products = $request->input('products', []);
        
        foreach ($products as $index => $product) {
            // Get variation to calculate proper tax and get product_id
            $variation = \App\Variation::with('product.product_tax')->find($product['variation_id']);
            
            if (!$variation) {
                throw new \Exception("Variation with ID {$product['variation_id']} not found");
            }
            
            $unit_price = $product['unit_price'];
            $tax_amount = 0;
            $tax_id = null;
            
            // Calculate tax if product has tax assigned
            if ($variation && $variation->product->product_tax) {
                $tax_id = $variation->product->product_tax->id;
                $tax_rate = $variation->product->product_tax->amount;
                
                // Calculate tax amount (assuming price includes tax)
                $tax_amount = ($unit_price * $tax_rate) / (100 + $tax_rate);
                $unit_price_exc_tax = $unit_price - $tax_amount;
            } else {
                $unit_price_exc_tax = $unit_price;
            }
            
            // Add required fields that TransactionUtil and ProductUtil expect
            $products[$index]['product_id'] = $variation->product_id; // CRITICAL: Required by TransactionUtil
            $products[$index]['product_type'] = $variation->product->type ?? 'single'; // Product type
            $products[$index]['enable_stock'] = $variation->product->enable_stock ?? 1; // Stock tracking flag
            $products[$index]['enable_product_expiry'] = $variation->product->enable_product_expiry ?? 0; // Expiry tracking
            $products[$index]['unit_price_inc_tax'] = $unit_price;
            $products[$index]['unit_price_exc_tax'] = round($unit_price_exc_tax, 4);
            $products[$index]['line_discount_type'] = 'fixed';
            $products[$index]['line_discount_amount'] = 0;
            $products[$index]['item_tax'] = round($tax_amount, 4);
            $products[$index]['tax_id'] = $tax_id;
            // Required by ProductUtil calculateInvoiceTotal
            $products[$index]['quantity'] = $product['quantity'];
        }
        
        $request->merge(['products' => $products]);
    }
    
    /**
     * Transform plastic bags for SellPosController
     */
    private function transformPlasticBagsForPosController(Request $request)
    {
        if (!$request->has('plastic_bags') || empty($request->plastic_bags)) {
            return;
        }
        
        $business_id = $this->getBusinessId();
        $pos_plastic_bag_data = [];
        
        foreach ($request->plastic_bags as $bag_data) {
            $type_id = $bag_data['type_id'];
            $quantity = $bag_data['quantity'];
            
            // Get plastic bag type to get the price
            $plastic_bag_type = PlasticBagType::forBusiness($business_id)
                ->where('id', $type_id)
                ->first();
                
            if (!$plastic_bag_type) {
                \Log::warning('Invalid plastic bag type ID in API request', [
                    'type_id' => $type_id,
                    'business_id' => $business_id
                ]);
                continue;
            }
            
            // Format as expected by SellPosController processPlasticBagUsage method
            // Structure: type_id => { quantity: X, price: Y }
            $pos_plastic_bag_data[$type_id] = [
                'quantity' => (float) $quantity,
                'price' => (float) $plastic_bag_type->price
            ];
        }
        
        if (!empty($pos_plastic_bag_data)) {
            // Set the data in the format SellPosController expects
            $request->merge(['pos_plastic_bag_data' => json_encode($pos_plastic_bag_data)]);
            
            \Log::info('Plastic bags transformed for POS controller', [
                'original_data' => $request->plastic_bags,
                'transformed_data' => $pos_plastic_bag_data,
                'business_id' => $business_id
            ]);
        }
    }
    
    /**
     * Process API-specific features before creating sale
     */
    private function processApiSpecificFeatures(Request $request)
    {
        // Process payment array from API format to POS format
        $payments = $request->input('payment', []);
        $processed_payment = [];
        
        foreach ($payments as $index => $payment) {
            $processed_payment[$index] = [
                'method' => $payment['method'],
                'amount' => $payment['amount'],
            ];
        }
        
        // Calculate total amount for credit limit check
        $products = $request->input('products', []);
        $product_total = 0;
        foreach ($products as $product) {
            $product_total += $product['quantity'] * $product['unit_price'];
        }
        
        // Add plastic bags total
        $bag_total = 0;
        if ($request->has('plastic_bags')) {
            foreach ($request->plastic_bags as $bag) {
                $bag_model = PlasticBagType::find($bag['type_id']);
                if ($bag_model) {
                    $bag_total += $bag['quantity'] * $bag_model->price;
                }
            }
        }
        
        $final_total = $product_total + $bag_total + ($request->input('shipping_charges', 0));
        
        // Add required fields that SellPosController expects
        $defaults = [
            'status' => 'final', // Required by SellPosController
            'is_direct_sale' => 1, // Set as direct sale
            'transaction_date' => $this->parseBusinessDate($request->input('transaction_date')), // Use business date format
            'invoice_no' => '', // Will be auto-generated
            'is_quotation' => 0,
            'final_total' => $final_total, // Required by TransactionUtil
            'payment' => $processed_payment,
            // Discount fields - REQUIRED by SellPosController
            'discount_type' => $request->input('discount_type', 'fixed'),
            'discount_amount' => $request->input('discount_amount', 0),
            // Tax fields
            'tax_rate_id' => $request->input('tax_rate_id', null),
            // Additional fields
            'shipping_charges' => $request->input('shipping_charges', 0),
            'shipping_details' => $request->input('shipping_details', ''),
            'additional_notes' => $request->input('additional_notes', ''),
            'staff_note' => $request->input('staff_note', ''),
            'commission_agent' => $request->input('commission_agent', 0)
        ];
        
        // Merge defaults with request
        $request->merge($defaults);
        
        // Set credit sale flag if specified
        if ($request->has('is_credit_sale') && $request->is_credit_sale) {
            $request->merge(['is_credit_sale' => 1]);
        }

        // Process discount
        if ($request->has('discount_amount')) {
            $request->merge([
                'discount_amount' => $request->discount_amount,
                'discount_type' => $request->discount_type ?? 'fixed'
            ]);
        }

        // Process tax
        if ($request->has('tax_id')) {
            $request->merge(['tax_rate_id' => $request->tax_id]);
        }

        // Process shipping
        if ($request->has('shipping_charges')) {
            $request->merge(['shipping_charges' => $request->shipping_charges]);
        }

        // Process commission agent
        if ($request->has('commission_agent')) {
            $request->merge(['commission_agent' => $request->commission_agent]);
        }

        // Process notes
        if ($request->has('sale_note')) {
            $request->merge(['sale_note' => $request->sale_note]);
        }
        if ($request->has('staff_note')) {
            $request->merge(['staff_note' => $request->staff_note]);
        }
    }


    /**
     * Search products with variations for POS
     *
     * @param int $business_id
     * @param string $term
     * @param int $location_id
     * @param int|null $category_id
     * @param int|null $brand_id
     * @return array
     */
    private function searchProductsForPOS($business_id, $term, $location_id, $category_id = null, $brand_id = null)
    {
        $query = \App\Variation::leftJoin('products as p', 'variations.product_id', '=', 'p.id')
            ->leftJoin('variation_location_details as vld', function($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id')
                     ->where('vld.location_id', $location_id);
            })
            ->leftJoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
            ->where('p.business_id', $business_id)
            ->where('p.type', '!=', 'modifier');

        // Search term
        if (!empty($term)) {
            $query->where(function($q) use ($term) {
                $q->where('p.name', 'like', '%' . $term . '%')
                  ->orWhere('variations.sub_sku', 'like', '%' . $term . '%')
                  ->orWhere('p.sku', 'like', '%' . $term . '%');
            });
        }

        // Filters
        if ($category_id) {
            $query->where('p.category_id', $category_id);
        }

        if ($brand_id) {
            $query->where('p.brand_id', $brand_id);
        }

        $products = $query->select([
                'variations.id as variation_id',
                'variations.name as variation_name',
                'variations.sub_sku',
                'p.id as product_id',
                'p.name as product_name',
                'p.sku as product_sku',
                'variations.default_sell_price',
                'vld.qty_available',
                'pv.name as variation_template_name'
            ])
            ->limit(50)
            ->get();

        return $products->map(function($product) {
            return [
                'variation_id' => $product->variation_id,
                'product_id' => $product->product_id,
                'name' => $product->product_name . ($product->variation_name ? ' (' . $product->variation_name . ')' : ''),
                'sku' => $product->sub_sku ?: $product->product_sku,
                'selling_price' => (float) $product->default_sell_price,
                'qty_available' => (float) ($product->qty_available ?: 0),
                'variation_template' => $product->variation_template_name
            ];
        })->toArray();
    }

    /**
     * Process products array - resolve SKUs to variation IDs
     */
    private function processProductSKUs(Request $request)
    {
        $products = $request->input('products', []);
        $business_id = $this->getBusinessId();
        
        foreach ($products as $index => $product) {
            // If SKU is provided but variation_id is not, resolve it
            if (!empty($product['sku']) && empty($product['variation_id'])) {
                $variation_id = $this->resolveSkuToVariationId($product['sku'], $business_id);
                
                if (!$variation_id) {
                    \Log::error('SKU Resolution Failed', [
                        'sku' => $product['sku'],
                        'business_id' => $business_id,
                        'index' => $index
                    ]);
                    throw new \Exception("Product with SKU '{$product['sku']}' not found");
                }
                
                \Log::info('SKU Resolution Success', [
                    'sku' => $product['sku'],
                    'variation_id' => $variation_id,
                    'business_id' => $business_id
                ]);
                
                // Replace SKU with variation_id in the request
                $products[$index]['variation_id'] = $variation_id;
                unset($products[$index]['sku']); // Remove SKU as it's no longer needed
            }
        }
        
        // Update the request with processed products
        $request->merge(['products' => $products]);
    }

    /**
     * Parse date according to business date format for SellPosController
     * Returns date in business format so uf_date() can convert it to MySQL format
     */
    private function parseBusinessDate($date_input = null): string
    {
        // Get business date format
        $business_id = $this->getBusinessId();
        $business = \App\Business::find($business_id);
        $date_format = $business->date_format ?? 'd/m/Y';
        $timezone = $business->time_zone ?? 'UTC';
        
        if (!$date_input) {
            // Use current date/time in business format so uf_date can process it
            // uf_date expects time component when called with true parameter
            $time_format = $business->time_format == 12 ? 'h:i A' : 'H:i';
            $full_format = $date_format . ' ' . $time_format;
            return now($timezone)->format($full_format);
        }
        
        // Build full datetime format expected by uf_date
        $time_format = $business->time_format == 12 ? 'h:i A' : 'H:i';
        $full_format = $date_format . ' ' . $time_format;
        
        try {
            // Try to parse input in various formats and convert to full business format
            $common_formats = [
                $date_format, // Date only (d/m/Y)
                $full_format, // Full datetime (d/m/Y H:i)
                'Y-m-d', 
                'd/m/Y', 
                'm/d/Y', 
                'Y-m-d H:i:s', 
                'd-m-Y', 
                'm-d-Y'
            ];
            
            foreach ($common_formats as $format) {
                try {
                    $parsed_date = \Carbon\Carbon::createFromFormat($format, $date_input);
                    if ($parsed_date) {
                        // Convert to full business datetime format for uf_date processing
                        return $parsed_date->format($full_format);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Final fallback - try Carbon's auto-parsing and convert to business format
            try {
                $parsed_date = \Carbon\Carbon::parse($date_input);
                return $parsed_date->format($full_format);
            } catch (\Exception $e) {
                // If all else fails, use current datetime in business format
                return now($timezone)->format($full_format);
            }
        } catch (\Exception $e) {
            // Ultimate fallback
            return now($timezone)->format($full_format);
        }
    }

    /**
     * Resolve SKU to variation ID
     */
    private function resolveSkuToVariationId(string $sku, int $business_id): ?int
    {
        // Try to find by variation sub_sku first (most specific)
        $variation = \DB::table('variations')
            ->join('products', 'variations.product_id', '=', 'products.id')
            ->where('products.business_id', $business_id)
            ->where('variations.sub_sku', $sku)
            ->select('variations.id')
            ->first();
            
        if ($variation) {
            return $variation->id;
        }
        
        // If not found, try by product SKU (for single products)
        $variation = \DB::table('variations')
            ->join('products', 'variations.product_id', '=', 'products.id')
            ->where('products.business_id', $business_id)
            ->where('products.sku', $sku)
            ->select('variations.id')
            ->first();
            
        return $variation ? $variation->id : null;
    }

    /**
     * Get the walk-in customer for the business
     */
    private function getWalkInCustomer()
    {
        $business_id = $this->getBusinessId();
        
        $walk_in_customer = \App\Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->where(function($query) {
                $query->where('name', 'like', '%walk%customer%')
                      ->orWhere('name', 'like', '%Walk%Customer%')
                      ->orWhere('name', 'like', '%walk-in%')
                      ->orWhere('name', 'like', '%Walk-In%');
            })
            ->first();

        if (!$walk_in_customer) {
            // Create a walk-in customer if it doesn't exist
            $walk_in_customer = \App\Contact::create([
                'business_id' => $business_id,
                'type' => 'customer',
                'name' => 'Walk-In Customer',
                'created_by' => auth()->id() ?? 1
            ]);
        }

        return $walk_in_customer;
    }
}