<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CategoryController extends BaseApiController
{
    /**
     * Display a listing of categories
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $params = $this->getPaginationParams($request);
            
            $query = Category::where('business_id', $business_id)
                ->where('category_type', 'product');

            // Apply filters
            $searchableFields = ['name', 'short_code'];
            $query = $this->applyFilters($query, $request, $searchableFields);

            // Filter by parent categories only or include sub-categories
            if ($request->boolean('parent_only', true)) {
                $query->where('parent_id', 0);
            }

            // Include sub-categories if requested
            if ($request->boolean('include_sub_categories')) {
                $query->with('sub_categories');
            }

            $query->orderBy('name', 'asc');

            $categories = $query->paginate($params['per_page']);

            return $this->paginatedResponse($categories, 'Categories retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve categories: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created category
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
                'short_code' => 'nullable|string|max:10',
                'parent_id' => 'nullable|integer|exists:categories,id',
                'description' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            // Validate parent category belongs to same business if provided
            if ($request->has('parent_id') && !empty($request->parent_id)) {
                $parentCategory = Category::where('business_id', $business_id)
                    ->where('id', $request->parent_id)
                    ->where('category_type', 'product')
                    ->first();
                    
                if (!$parentCategory) {
                    return $this->errorResponse('Invalid parent_id: Category does not belong to your business', 422);
                }
            }

            // Prepare category data before transaction
            $categoryData = $validator->validated();
            $categoryData['business_id'] = $business_id;
            $categoryData['category_type'] = 'product';
            $categoryData['parent_id'] = $categoryData['parent_id'] ?? 0;
            $categoryData['created_by'] = auth()->id() ?? 1;

            // Check for duplicate name within the same business and parent
            // Use optimized single query with case-insensitive check
            $existingCategory = Category::where([
                ['business_id', '=', $business_id],
                ['category_type', '=', 'product'],
                ['parent_id', '=', $categoryData['parent_id']]
            ])->where(function($query) use ($categoryData) {
                $query->where('name', '=', $categoryData['name'])
                      ->orWhereRaw('LOWER(name) = LOWER(?)', [$categoryData['name']]);
            })->first();
            
            if ($existingCategory) {
                return $this->errorResponse('Category with this name already exists', 422);
            }

            // Create the category (no transaction needed for single insert)
            $category = Category::create($categoryData);

            return $this->successResponse(
                $category,
                'Category created successfully',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create category: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified category
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $query = Category::where('business_id', $business_id)
                ->where('category_type', 'product')
                ->where('id', $id);

            // Include sub-categories if requested
            if ($request->boolean('include_sub_categories')) {
                $query->with('sub_categories');
            }

            $category = $query->first();

            if (!$category) {
                return $this->errorResponse('Category not found', 404);
            }

            return $this->successResponse(
                $category,
                'Category retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve category: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified category
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $category = Category::where('business_id', $business_id)
                ->where('category_type', 'product')
                ->find($id);
            
            if (!$category) {
                return $this->errorResponse('Category not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'short_code' => 'nullable|string|max:10',
                'parent_id' => 'nullable|integer|exists:categories,id',
                'description' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            // Validate parent category belongs to same business if provided
            if ($request->has('parent_id') && !empty($request->parent_id)) {
                $parentCategory = Category::where('business_id', $business_id)
                    ->where('id', $request->parent_id)
                    ->where('category_type', 'product')
                    ->first();
                    
                if (!$parentCategory) {
                    return $this->errorResponse('Invalid parent_id: Category does not belong to your business', 422);
                }
                
                // Prevent setting parent to self or descendant
                if ($request->parent_id == $id) {
                    return $this->errorResponse('Category cannot be its own parent', 422);
                }
            }

            DB::beginTransaction();

            $categoryData = $validator->validated();
            
            // Check for duplicate name within the same business and parent (if name is being updated)
            if ($request->has('name') && $request->name !== $category->name) {
                $parent_id = $request->has('parent_id') ? ($request->parent_id ?? 0) : $category->parent_id;
                
                // Use optimized duplicate check
                $existingCategory = Category::where([
                    ['business_id', '=', $business_id],
                    ['category_type', '=', 'product'],
                    ['parent_id', '=', $parent_id],
                    ['id', '!=', $id],
                    ['name', '=', $request->name]
                ])->first();
                
                // If exact match not found, check case-insensitive
                if (!$existingCategory) {
                    $existingCategory = Category::where([
                        ['business_id', '=', $business_id],
                        ['category_type', '=', 'product'],
                        ['parent_id', '=', $parent_id],
                        ['id', '!=', $id]
                    ])->whereRaw('LOWER(name) = LOWER(?)', [$request->name])
                    ->first();
                }
                    
                if ($existingCategory) {
                    return $this->errorResponse('Category with this name already exists', 422);
                }
            }

            $category->update($categoryData);

            DB::commit();

            return $this->successResponse(
                $category,
                'Category updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update category: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified category
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $category = Category::where('business_id', $business_id)
                ->where('category_type', 'product')
                ->find($id);
            
            if (!$category) {
                return $this->errorResponse('Category not found', 404);
            }

            DB::beginTransaction();

            // Check if category has sub-categories
            $hasSubCategories = Category::where('business_id', $business_id)
                ->where('parent_id', $id)
                ->exists();

            if ($hasSubCategories) {
                return $this->errorResponse('Cannot delete category that has sub-categories. Delete sub-categories first.', 422);
            }

            // Check if category is used by any products
            $hasProducts = DB::table('products')
                ->where('business_id', $business_id)
                ->where(function($q) use ($id) {
                    $q->where('category_id', $id)
                      ->orWhere('sub_category_id', $id);
                })
                ->exists();

            if ($hasProducts) {
                return $this->errorResponse('Cannot delete category that is being used by products', 422);
            }

            // Delete the category (soft delete)
            $category->delete();

            DB::commit();

            return $this->successResponse(null, 'Category deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete category: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get categories in dropdown format
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dropdown(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $categories = Category::forDropdown($business_id, 'product');

            return $this->successResponse(
                $categories,
                'Categories dropdown retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve categories dropdown: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get categories with sub-categories in hierarchical format
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hierarchical(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            // Check if pagination is requested
            if ($request->has('per_page')) {
                $params = $this->getPaginationParams($request);
                
                // Get parent categories with pagination, then load their children
                $parentCategories = Category::where('business_id', $business_id)
                    ->where('category_type', 'product')
                    ->where('parent_id', 0)
                    ->with('sub_categories')
                    ->orderBy('name', 'asc')
                    ->paginate($params['per_page']);

                return $this->paginatedResponse($parentCategories, 'Hierarchical categories retrieved successfully');
            } else {
                // Return all categories (existing behavior)
                $categories = Category::catAndSubCategories($business_id);

                return $this->successResponse(
                    $categories,
                    'Hierarchical categories retrieved successfully'
                );
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve hierarchical categories: ' . $e->getMessage(), 500);
        }
    }
}