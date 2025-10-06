<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseApiController extends Controller
{
    /**
     * Return successful JSON response
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $response['meta'] = [
            'timestamp' => now()->toISOString(),
            'version' => 'v1'
        ];

        return response()->json($response, $code);
    }

    /**
     * Return error JSON response
     *
     * @param string $message
     * @param int $code
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Error', int $code = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        $response['meta'] = [
            'timestamp' => now()->toISOString(),
            'version' => 'v1'
        ];

        return response()->json($response, $code);
    }

    /**
     * Return paginated JSON response
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @return JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => 'v1',
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                    'has_more_pages' => $paginator->hasMorePages(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl()
                ]
            ]
        ]);
    }

    /**
     * Return resource JSON response
     *
     * @param JsonResource $resource
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function resourceResponse(JsonResource $resource, string $message = 'Success', int $code = 200): JsonResponse
    {
        $data = $resource->resolve();
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => 'v1'
            ]
        ], $code);
    }

    /**
     * Return collection resource JSON response
     *
     * @param \Illuminate\Http\Resources\Json\ResourceCollection $collection
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function collectionResponse($collection, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = $collection->response()->getData(true);
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $response['data'],
            'meta' => array_merge(
                $response['meta'] ?? [],
                [
                    'timestamp' => now()->toISOString(),
                    'version' => 'v1'
                ]
            )
        ], $code);
    }

    /**
     * Get business ID from request
     *
     * @return int
     */
    protected function getBusinessId(): int
    {
        return request()->attributes->get('business_id');
    }

    /**
     * Get API key model from request
     *
     * @return \App\ApiKey
     */
    protected function getApiKey(): \App\ApiKey
    {
        return request()->attributes->get('api_key');
    }

    /**
     * Get business model from request
     *
     * @return \App\Business
     */
    protected function getBusiness(): \App\Business
    {
        return request()->attributes->get('business');
    }

    /**
     * Validate pagination parameters
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function getPaginationParams($request): array
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100); // Between 1 and 100
        
        $page = (int) $request->get('page', 1);
        $page = max($page, 1); // At least 1

        return [
            'per_page' => $perPage,
            'page' => $page
        ];
    }

    /**
     * Apply common filters to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @param array $searchableFields
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters($query, $request, array $searchableFields = []): \Illuminate\Database\Eloquent\Builder
    {
        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }

        // Date range filtering
        if ($dateFrom = $request->get('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo = $request->get('date_to')) {
            $query->where('created_at', '<=', $dateTo);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        
        if (in_array($sortDir, ['asc', 'desc'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query;
    }
}