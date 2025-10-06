<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicInfoController extends BaseApiController
{
    /**
     * Get public API information (no authentication required)
     */
    public function info(Request $request): JsonResponse
    {
        $info = [
            'api_name' => 'IsleBooks POS API',
            'api_version' => 'v1',
            'server_time' => now()->toISOString(),
            'documentation_url' => url('/api-docs'),
            'authentication' => [
                'type' => 'API Key',
                'methods' => [
                    'header' => 'Authorization: Bearer YOUR_API_KEY',
                    'header_alt' => 'X-API-Key: YOUR_API_KEY',
                    'query' => '?api_key=YOUR_API_KEY'
                ]
            ],
            'rate_limits' => [
                'default' => '60 requests per minute',
                'varies_by_key' => true
            ],
            'features' => [
                'pos' => 'Point of Sale operations',
                'inventory' => 'Product and stock management', 
                'contacts' => 'Customer and supplier management',
                'transactions' => 'Sales and purchase tracking',
                'reports' => 'Business analytics and reporting',
                'webhooks' => 'Real-time event notifications'
            ],
            'data_formats' => [
                'request' => 'application/json',
                'response' => 'application/json'
            ],
            'endpoints' => [
                'health' => '/api/status',
                'business_info' => '/api/v1/business',
                'products' => '/api/v1/products',
                'contacts' => '/api/v1/contacts',
                'sales' => '/api/v1/sales',
                'reports' => '/api/v1/reports'
            ],
            'support' => [
                'documentation' => url('/api-docs'),
                'playground' => url('/api-playground'),
                'contact' => 'API support available through your business dashboard'
            ]
        ];

        return $this->sendSuccess('Public API information retrieved', $info);
    }

    /**
     * Get API status and health
     */
    public function status(Request $request): JsonResponse
    {
        $status = [
            'status' => 'operational',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'uptime' => 'Monitoring available to authenticated users',
            'services' => [
                'api' => 'operational',
                'database' => 'operational',
                'cache' => 'operational'
            ]
        ];

        return $this->sendSuccess('API status retrieved', $status);
    }

    /**
     * Get available API versions
     */
    public function versions(Request $request): JsonResponse
    {
        $versions = [
            'current' => 'v1',
            'supported' => ['v1'],
            'deprecated' => [],
            'sunset_dates' => [],
            'changelog_url' => url('/api-docs#changelog'),
            'migration_guides' => []
        ];

        return $this->sendSuccess('API versions retrieved', $versions);
    }

    /**
     * Get rate limit information
     */
    public function rateLimits(Request $request): JsonResponse
    {
        $limits = [
            'default_limit' => 60,
            'window' => '1 minute',
            'varies_by_key' => true,
            'headers' => [
                'X-RateLimit-Limit' => 'Maximum requests per window',
                'X-RateLimit-Remaining' => 'Remaining requests in current window',
                'X-RateLimit-Reset' => 'Window reset time (Unix timestamp)'
            ],
            'upgrade_info' => 'Higher rate limits available with premium API keys'
        ];

        return $this->sendSuccess('Rate limit information retrieved', $limits);
    }
}