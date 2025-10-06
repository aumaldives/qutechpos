<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Brands;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BrandController extends BaseApiController
{
    /**
     * Display a listing of brands
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $params = $this->getPaginationParams($request);
            
            $query = Brands::where('business_id', $business_id);

            // Apply filters
            $searchableFields = ['name', 'description'];
            $query = $this->applyFilters($query, $request, $searchableFields);

            // Filter by repair usage
            if ($request->has('use_for_repair')) {
                $query->where('use_for_repair', $request->boolean('use_for_repair'));
            }

            $query->orderBy('name', 'asc');

            $brands = $query->paginate($params['per_page']);

            return $this->paginatedResponse($brands, 'Brands retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve brands: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created brand
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
                'description' => 'nullable|string|max:1000',
                'use_for_repair' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            // Prepare brand data
            $brandData = $validator->validated();
            $brandData['business_id'] = $business_id;
            $brandData['created_by'] = auth()->id() ?? 1;

            // Check for duplicate name within the same business (optimized)
            $existingBrand = Brands::where('business_id', $business_id)
                ->where(function($query) use ($brandData) {
                    $query->where('name', '=', $brandData['name'])
                          ->orWhereRaw('LOWER(name) = LOWER(?)', [$brandData['name']]);
                })->first();
                
            if ($existingBrand) {
                return $this->errorResponse('Brand with this name already exists', 422);
            }

            // Create the brand (no transaction needed for single insert)
            $brand = Brands::create($brandData);

            return $this->successResponse(
                $brand,
                'Brand created successfully',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create brand: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified brand
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $brand = Brands::where('business_id', $business_id)->find($id);

            if (!$brand) {
                return $this->errorResponse('Brand not found', 404);
            }

            return $this->successResponse(
                $brand,
                'Brand retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve brand: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified brand
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $brand = Brands::where('business_id', $business_id)->find($id);
            
            if (!$brand) {
                return $this->errorResponse('Brand not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:1000',
                'use_for_repair' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            $brandData = $validator->validated();
            
            // Check for duplicate name within the same business (if name is being updated)
            if ($request->has('name') && $request->name !== $brand->name) {
                $existingBrand = Brands::where('business_id', $business_id)
                    ->where('id', '!=', $id)
                    ->where(function($query) use ($request) {
                        $query->where('name', '=', $request->name)
                              ->orWhereRaw('LOWER(name) = LOWER(?)', [$request->name]);
                    })->first();
                    
                if ($existingBrand) {
                    return $this->errorResponse('Brand with this name already exists', 422);
                }
            }

            $brand->update($brandData);

            return $this->successResponse(
                $brand,
                'Brand updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update brand: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified brand
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $brand = Brands::where('business_id', $business_id)->find($id);
            
            if (!$brand) {
                return $this->errorResponse('Brand not found', 404);
            }

            DB::beginTransaction();

            // Check if brand is used by any products
            $hasProducts = DB::table('products')
                ->where('business_id', $business_id)
                ->where('brand_id', $id)
                ->exists();

            if ($hasProducts) {
                return $this->errorResponse('Cannot delete brand that is being used by products', 422);
            }

            // Delete the brand (soft delete)
            $brand->delete();

            DB::commit();

            return $this->successResponse(null, 'Brand deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete brand: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get brands in dropdown format
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dropdown(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $filter_repair = $request->boolean('filter_repair', false);
            $brands = Brands::forDropdown($business_id, false, $filter_repair);

            return $this->successResponse(
                $brands,
                'Brands dropdown retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve brands dropdown: ' . $e->getMessage(), 500);
        }
    }
}