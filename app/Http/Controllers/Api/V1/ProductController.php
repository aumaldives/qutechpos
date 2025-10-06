<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\ProductResource;
use App\Product;
use App\Variation;
use App\Utils\ProductUtil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseApiController
{
    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    /**
     * Display a listing of products
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $params = $this->getPaginationParams($request);
            
            $query = Product::where('business_id', $business_id)
                ->with(['unit', 'category', 'sub_category', 'brand', 'warranty']);

            // Apply filters
            $searchableFields = ['name', 'sku', 'product_description'];
            $query = $this->applyFilters($query, $request, $searchableFields);

            // Additional filters
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('is_inactive')) {
                $query->where('is_inactive', $request->boolean('is_inactive'));
            }

            if ($request->has('not_for_selling')) {
                $query->where('not_for_selling', $request->boolean('not_for_selling'));
            }

            // Handle location filtering if provided
            $location = null;
            if ($request->has('location_id')) {
                $location_id = $request->location_id;
                
                // Validate location belongs to business
                $location = \App\BusinessLocation::where('business_id', $business_id)
                    ->where(function($q) use ($location_id) {
                        $q->where('location_id', $location_id)
                          ->orWhere('id', $location_id);
                    })
                    ->first();
                    
                if (!$location) {
                    return $this->errorResponse('Invalid location_id: Location does not belong to your business', 403);
                }
                
                // Only return products that have variations with stock at this specific location
                $query->whereHas('variations.variation_location_details', function($q) use ($location) {
                    $q->where('location_id', $location->id);
                });
            }

            // Include variations and stock if requested
            if ($request->boolean('include_variations')) {
                $query->with(['variations', 'variations.variation_value', 'variations.variation_template']);
            }

            if ($request->boolean('include_stock')) {
                if ($location) {
                    // Filter stock to specific location (already validated)
                    $query->with(['variations.variation_location_details' => function($q) use ($location) {
                        $q->where('location_id', $location->id);
                    }]);
                } else {
                    // Include stock from all locations
                    $query->with(['variations.variation_location_details']);
                }
            }

            $products = $query->paginate($params['per_page']);

            return $this->paginatedResponse($products, 'Products retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve products: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created product
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'type' => 'nullable|in:single,variable,combo', // Default to single
                'unit_id' => 'required|integer|exists:units,id',
                'sku' => 'nullable|string|max:255|unique:products,sku,NULL,id,business_id,' . $business_id,
                'barcode_type' => 'nullable|string|in:C128,C39,EAN13,EAN8,UPCA,UPCE',
                
                // Category - support both ID and name
                'category_id' => 'nullable|integer|exists:categories,id',
                'category_name' => 'nullable|string|max:255',
                'sub_category_id' => 'nullable|integer|exists:categories,id',
                
                // Brand - support both ID and name
                'brand_id' => 'nullable|integer|exists:brands,id', 
                'brand_name' => 'nullable|string|max:255',
                
                'tax' => 'nullable|numeric|min:0|max:100',
                'tax_type' => 'nullable|in:exclusive,inclusive',
                'enable_stock' => 'nullable|boolean',
                'alert_quantity' => 'nullable|numeric|min:0',
                'weight' => 'nullable|numeric|min:0',
                'warranty_id' => 'nullable|integer|exists:warranties,id',
                'is_inactive' => 'nullable|boolean',
                'not_for_selling' => 'nullable|boolean',
                'product_description' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                
                // Simplified pricing for single products (not required for variations)
                'default_purchase_price' => 'nullable|numeric|min:0',
                'default_sell_price' => 'nullable|numeric|min:0',
                'profit_percent' => 'nullable|numeric|min:0|max:100',
                
                // Variations for variable products (optional)
                'variations' => 'nullable|array',
                'variations.*.name' => 'required_with:variations|string|max:255',
                'variations.*.default_purchase_price' => 'required_with:variations|numeric|min:0',
                'variations.*.dpp_inc_tax' => 'nullable|numeric|min:0',
                'variations.*.profit_percent' => 'nullable|numeric|min:0|max:100',
                'variations.*.default_sell_price' => 'required_with:variations|numeric|min:0',
                'variations.*.sell_price_inc_tax' => 'nullable|numeric|min:0',
                'variations.*.sub_sku' => 'nullable|string|max:255',
                
                // Opening stock (required - at least one location)
                'opening_stock' => 'required|array|min:1',
                'opening_stock.*.location_id' => 'required|string', // Can be location code (BL0003) or table ID
                'opening_stock.*.variation_id' => 'nullable|integer', // Will be set after variation creation
                'opening_stock.*.quantity' => 'required|numeric|min:0',
                'opening_stock.*.unit_price' => 'required|numeric|min:0',
            ]);
            
            // Handle query parameters
            $auto_sku = $request->boolean('auto_sku', false);
            $enable_stock_default = $request->boolean('enable_stock', true);
            
            // Category is optional, but if provided, must be valid
            // No additional validation needed - both category_id and category_name are optional

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            // Validate and convert location IDs in opening stock
            $openingStock = $request->input('opening_stock', []);
            
            // Handle form-encoded JSON string for opening_stock
            if (is_string($openingStock)) {
                $openingStock = json_decode($openingStock, true);
                if (!is_array($openingStock)) {
                    return $this->errorResponse('Invalid opening_stock JSON format', 422);
                }
            }
            
            $validatedOpeningStock = [];
            
            foreach ($openingStock as $index => $stockData) {
                $location_id = $stockData['location_id'];
                
                // Validate location belongs to business (accept both location codes and table IDs)
                $location = \App\BusinessLocation::where('business_id', $business_id)
                    ->where(function($q) use ($location_id) {
                        $q->where('location_id', $location_id)
                          ->orWhere('id', $location_id);
                    })
                    ->first();
                    
                if (!$location) {
                    return $this->errorResponse(
                        "Invalid location_id '{$location_id}' in opening_stock[{$index}]: Location does not belong to your business", 
                        422
                    );
                }
                
                // Convert to table ID for database operations
                $stockData['location_id'] = $location->id;
                $validatedOpeningStock[] = $stockData;
            }

            DB::beginTransaction();

            // Prepare product data
            $productData = $validator->validated();
            $productData['business_id'] = $business_id;
            $productData['created_by'] = auth()->id() ?? 1; // Default to system user for API
            
            // Set default type if not provided
            if (!isset($productData['type'])) {
                $productData['type'] = 'single';
            }
            
            // Auto-generate SKU if requested and not provided
            if ($auto_sku && empty($productData['sku'])) {
                $productData['sku'] = strtoupper(str_replace(' ', '', substr($productData['name'], 0, 10)));
            }
            
            // Set enable_stock default if not provided
            if (!isset($productData['enable_stock'])) {
                $productData['enable_stock'] = $enable_stock_default;
            }
            
            // Handle category - find or create by name
            if ($request->has('category_name') && !$request->has('category_id')) {
                $categoryName = $request->category_name;
                $category = \App\Category::where('business_id', $business_id)
                    ->where('category_type', 'product')
                    ->where('parent_id', 0)
                    ->whereRaw('LOWER(name) = ?', [strtolower($categoryName)])
                    ->first();
                    
                if (!$category) {
                    // Create new category
                    $category = \App\Category::create([
                        'name' => $categoryName,
                        'business_id' => $business_id,
                        'category_type' => 'product',
                        'parent_id' => 0,
                        'created_by' => auth()->id() ?? 1
                    ]);
                }
                
                $productData['category_id'] = $category->id;
            }
            
            // Handle brand - find or create by name
            if ($request->has('brand_name') && !$request->has('brand_id')) {
                $brandName = $request->brand_name;
                $brand = \App\Brands::where('business_id', $business_id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($brandName)])
                    ->first();
                    
                if (!$brand) {
                    // Create new brand
                    $brand = \App\Brands::create([
                        'name' => $brandName,
                        'business_id' => $business_id,
                        'created_by' => auth()->id() ?? 1
                    ]);
                }
                
                $productData['brand_id'] = $brand->id;
            }
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $imageName = time() . '.' . $request->image->extension();
                $request->image->move(public_path('uploads/img'), $imageName);
                $productData['image'] = $imageName;
            }

            // Remove helper fields and prepare data
            $variations = $productData['variations'] ?? [];
            unset($productData['variations'], $productData['opening_stock'], $productData['category_name'], $productData['brand_name']);
            
            // For single products, prepare default variation data from product data
            $singleProductVariation = [
                'default_purchase_price' => $productData['default_purchase_price'] ?? 0,
                'default_sell_price' => $productData['default_sell_price'] ?? 0, 
                'profit_percent' => $productData['profit_percent'] ?? 0
            ];
            
            // Remove pricing fields from product data as they belong to variations
            unset($productData['default_purchase_price'], $productData['default_sell_price'], $productData['profit_percent']);

            // Create the product
            $product = Product::create($productData);

            // Handle variations
            if ($product->type === 'variable' && !empty($variations)) {
                foreach ($variations as $variationData) {
                    // First create ProductVariation 
                    $productVariation = \App\ProductVariation::create([
                        'product_id' => $product->id,
                        'name' => $variationData['name'],
                        'is_dummy' => 0,
                    ]);
                    
                    // Then create Variation with proper product_variation_id
                    $variationData['product_id'] = $product->id;
                    $variationData['product_variation_id'] = $productVariation->id; // This was missing!
                    $variation = Variation::create($variationData);
                    
                    // Create opening stock for this variation
                    if (!empty($validatedOpeningStock)) {
                        foreach ($validatedOpeningStock as $stockData) {
                            \App\VariationLocationDetails::create([
                                'product_id' => $product->id,
                                'product_variation_id' => $productVariation->id,
                                'variation_id' => $variation->id,
                                'location_id' => $stockData['location_id'], // Already converted to table ID
                                'qty_available' => $stockData['quantity'],
                            ]);
                        }
                    }
                }
            } elseif ($product->type === 'single') {
                // Create default product variation first
                $productVariation = \App\ProductVariation::create([
                    'product_id' => $product->id,
                    'name' => 'DUMMY',
                    'is_dummy' => 1,
                ]);
                
                // Create default variation for single products using product-level pricing
                $defaultVariation = [
                    'product_id' => $product->id,
                    'product_variation_id' => $productVariation->id, // This was missing!
                    'name' => 'DUMMY',
                    'default_purchase_price' => $singleProductVariation['default_purchase_price'],
                    'dpp_inc_tax' => $singleProductVariation['default_purchase_price'], // Same as purchase price for simplicity
                    'profit_percent' => $singleProductVariation['profit_percent'],
                    'default_sell_price' => $singleProductVariation['default_sell_price'],
                    'sell_price_inc_tax' => $singleProductVariation['default_sell_price'], // Same as sell price for simplicity
                ];
                
                $variation = Variation::create($defaultVariation);
                
                // Create opening stock
                if (!empty($validatedOpeningStock)) {
                    foreach ($validatedOpeningStock as $stockData) {
                        \App\VariationLocationDetails::create([
                            'product_id' => $product->id,
                            'product_variation_id' => $productVariation->id,
                            'variation_id' => $variation->id,
                            'location_id' => $stockData['location_id'], // Already converted to table ID
                            'qty_available' => $stockData['quantity'],
                        ]);
                    }
                }
            }

            DB::commit();

            // Reload product with relationships
            $product->load(['unit', 'category', 'brand', 'variations']);

            return $this->resourceResponse(
                new ProductResource($product),
                'Product created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified product
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $query = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['unit', 'category', 'sub_category', 'brand', 'warranty']);

            // Handle location filtering if provided
            $location = null;
            if ($request->has('location_id')) {
                $location_id = $request->location_id;
                
                // Validate location belongs to business
                $location = \App\BusinessLocation::where('business_id', $business_id)
                    ->where(function($q) use ($location_id) {
                        $q->where('location_id', $location_id)
                          ->orWhere('id', $location_id);
                    })
                    ->first();
                    
                if (!$location) {
                    return $this->errorResponse('Invalid location_id: Location does not belong to your business', 403);
                }
                
                // Check if product has stock at this location
                $query->whereHas('variations.variation_location_details', function($q) use ($location) {
                    $q->where('location_id', $location->id);
                });
            }

            if ($request->boolean('include_variations')) {
                $query->with(['variations', 'variations.variation_value', 'variations.variation_template']);
            }

            if ($request->boolean('include_stock')) {
                if ($location) {
                    // Filter stock to specific location (already validated)
                    $query->with(['variations.variation_location_details' => function($q) use ($location) {
                        $q->where('location_id', $location->id);
                    }]);
                } else {
                    // Include stock from all locations
                    $query->with(['variations.variation_location_details']);
                }
            }

            if ($request->boolean('include_modifiers')) {
                $query->with(['modifier_sets', 'modifier_sets.modifiers']);
            }

            $product = $query->first();

            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            return $this->resourceResponse(
                new ProductResource($product),
                'Product retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified product
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $product = Product::where('business_id', $business_id)->find($id);
            
            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'unit_id' => 'sometimes|integer|exists:units,id',
                'sku' => 'sometimes|string|max:255|unique:products,sku,' . $id . ',id,business_id,' . $business_id,
                'barcode_type' => 'nullable|string|in:C128,C39,EAN13,EAN8,UPCA,UPCE',
                'category_id' => 'sometimes|integer|exists:categories,id',
                'sub_category_id' => 'nullable|integer|exists:categories,id',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'tax' => 'nullable|numeric|min:0|max:100',
                'tax_type' => 'nullable|in:exclusive,inclusive',
                'enable_stock' => 'nullable|boolean',
                'alert_quantity' => 'nullable|numeric|min:0',
                'weight' => 'nullable|numeric|min:0',
                'warranty_id' => 'nullable|integer|exists:warranties,id',
                'is_inactive' => 'nullable|boolean',
                'not_for_selling' => 'nullable|boolean',
                'product_description' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            DB::beginTransaction();

            $productData = $validator->validated();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($product->image && file_exists(public_path('uploads/img/' . $product->image))) {
                    unlink(public_path('uploads/img/' . $product->image));
                }
                
                $imageName = time() . '.' . $request->image->extension();
                $request->image->move(public_path('uploads/img'), $imageName);
                $productData['image'] = $imageName;
            }

            $product->update($productData);

            DB::commit();

            // Reload product with relationships
            $product->load(['unit', 'category', 'brand', 'variations']);

            return $this->resourceResponse(
                new ProductResource($product),
                'Product updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified product
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $product = Product::where('business_id', $business_id)->find($id);
            
            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            DB::beginTransaction();

            // Check if product is used in any transactions
            $hasTransactions = DB::table('transaction_sell_lines')
                ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
                ->where('variations.product_id', $id)
                ->exists();

            if ($hasTransactions) {
                return $this->errorResponse('Cannot delete product that has been used in transactions', 422);
            }

            // Delete product image if exists
            if ($product->image && file_exists(public_path('uploads/img/' . $product->image))) {
                unlink(public_path('uploads/img/' . $product->image));
            }

            // Delete variations and related data
            $product->variations()->delete();
            
            // Delete the product
            $product->delete();

            DB::commit();

            return $this->successResponse(null, 'Product deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get product variations
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function variations(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $product = Product::where('business_id', $business_id)->find($id);
            
            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            $query = $product->variations()
                ->with(['variation_value', 'variation_template']);

            if ($request->boolean('include_stock')) {
                $query->with('variation_location_details');
            }

            $variations = $query->get();

            return $this->successResponse(
                $variations->map(function ($variation) {
                    return new \App\Http\Resources\VariationResource($variation);
                }),
                'Product variations retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve product variations: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get product stock information
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function stock(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $product = Product::where('business_id', $business_id)->find($id);
            
            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            $location_id = $request->get('location_id');
            
            $stockData = $this->productUtil->getVariationStockDetails(
                $business_id,
                $id,
                $location_id
            );

            return $this->successResponse(
                $stockData,
                'Product stock information retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve stock information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk store products
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $validator = Validator::make($request->all(), [
                'products' => 'required|array|min:1|max:100', // Limit bulk operations
                'products.*.name' => 'required|string|max:255',
                'products.*.type' => 'required|in:single,variable',
                'products.*.unit_id' => 'required|integer|exists:units,id',
                'products.*.category_id' => 'required|integer|exists:categories,id',
                // Add other validation rules as needed
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            DB::beginTransaction();

            $createdProducts = [];
            $errors = [];

            foreach ($request->products as $index => $productData) {
                try {
                    $productData['business_id'] = $business_id;
                    $productData['created_by'] = auth()->id() ?? 1;
                    
                    $product = Product::create($productData);
                    
                    // Create default variation for single products
                    if ($product->type === 'single') {
                        Variation::create([
                            'product_id' => $product->id,
                            'name' => 'DUMMY',
                            'default_purchase_price' => $productData['default_purchase_price'] ?? 0,
                            'default_sell_price' => $productData['default_sell_price'] ?? 0,
                        ]);
                    }
                    
                    $createdProducts[] = new ProductResource($product);
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'product' => $productData['name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return $this->successResponse([
                'created_products' => $createdProducts,
                'total_created' => count($createdProducts),
                'total_errors' => count($errors),
                'errors' => $errors
            ], count($createdProducts) . ' products created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Bulk product creation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk update products
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $validator = Validator::make($request->all(), [
                'products' => 'required|array|min:1|max:100',
                'products.*.id' => 'required|integer|exists:products,id',
                // Add other validation rules as needed
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            DB::beginTransaction();

            $updatedProducts = [];
            $errors = [];

            foreach ($request->products as $index => $productData) {
                try {
                    $product = Product::where('business_id', $business_id)
                        ->find($productData['id']);
                    
                    if (!$product) {
                        $errors[] = [
                            'index' => $index,
                            'id' => $productData['id'],
                            'error' => 'Product not found'
                        ];
                        continue;
                    }
                    
                    $updateData = collect($productData)->except(['id'])->toArray();
                    $product->update($updateData);
                    
                    $updatedProducts[] = new ProductResource($product);
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'id' => $productData['id'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return $this->successResponse([
                'updated_products' => $updatedProducts,
                'total_updated' => count($updatedProducts),
                'total_errors' => count($errors),
                'errors' => $errors
            ], count($updatedProducts) . ' products updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Bulk product update failed: ' . $e->getMessage(), 500);
        }
    }
}