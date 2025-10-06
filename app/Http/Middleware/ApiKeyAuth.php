<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\ApiKey;
use App\ApiUsageLog;
use Illuminate\Http\JsonResponse;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  $ability  Optional ability requirement (e.g., 'read', 'write', 'products')
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ?string $ability = null)
    {
        $start_time = microtime(true);
        
        // Get API key from header or query parameter
        $api_key = $this->extractApiKey($request);
        
        if (!$api_key) {
            return $this->unauthorizedResponse('API key is required. Include it in Authorization header as "Bearer YOUR_API_KEY" or api_key parameter.');
        }

        // Find and validate API key
        $api_key_model = ApiKey::findValidKey($api_key);
        
        if (!$api_key_model) {
            $this->logFailedRequest($request, $api_key, 401, 'Invalid or expired API key', $start_time);
            return $this->unauthorizedResponse('Invalid or expired API key.');
        }

        // Check rate limiting
        $rate_limit_result = ApiUsageLog::checkRateLimit($api_key_model);
        
        if (!$rate_limit_result['allowed']) {
            $this->logFailedRequest($request, $api_key, 429, 'Rate limit exceeded', $start_time, $api_key_model);
            return $this->rateLimitResponse($rate_limit_result);
        }

        // Check specific ability if required
        if ($ability && !$api_key_model->hasAbility($ability)) {
            $this->logFailedRequest($request, $api_key, 403, "Missing required ability: $ability", $start_time, $api_key_model);
            return $this->forbiddenResponse("This API key does not have the required '$ability' permission.");
        }

        // Set business context for the request
        $request->attributes->set('api_key', $api_key_model);
        $request->attributes->set('business_id', $api_key_model->business_id);
        $request->attributes->set('business', $api_key_model->business);
        $request->attributes->set('api_user', $api_key_model->user); // Store user without logging in

        // Update last used timestamp
        $api_key_model->markAsUsed();

        // Continue with the request
        $response = $next($request);

        // Log successful request
        $this->logSuccessfulRequest($request, $response, $api_key_model, $start_time);

        // Add rate limit headers to response
        $this->addRateLimitHeaders($response, $rate_limit_result);

        return $response;
    }

    /**
     * Extract API key from request
     *
     * @param Request $request
     * @return string|null
     */
    private function extractApiKey(Request $request): ?string
    {
        // Try Authorization header first (Bearer token)
        $authorization = $request->header('Authorization');
        if ($authorization && preg_match('/Bearer\s+(.+)/', $authorization, $matches)) {
            return $matches[1];
        }

        // Try X-API-Key header
        $api_key = $request->header('X-API-Key');
        if ($api_key) {
            return $api_key;
        }

        // Try query parameter
        return $request->query('api_key');
    }

    /**
     * Log failed API request
     *
     * @param Request $request
     * @param string $api_key
     * @param int $status_code
     * @param string $error_message
     * @param float $start_time
     * @param ApiKey|null $api_key_model
     */
    private function logFailedRequest(Request $request, string $api_key, int $status_code, string $error_message, float $start_time, ?ApiKey $api_key_model = null): void
    {
        $response_time = (int)((microtime(true) - $start_time) * 1000);

        // If we have a valid API key model, log with proper relationships
        if ($api_key_model) {
            ApiUsageLog::logRequest(
                $api_key_model,
                $request->getPathInfo(),
                $request->method(),
                $request->ip(),
                $request->userAgent(),
                $status_code,
                $response_time,
                $this->getRequestData($request),
                ['error' => $error_message],
                $error_message
            );
        }
        // Otherwise, we can't log to the database properly, so just log to Laravel log
        else {
            \Log::warning('API authentication failed', [
                'api_key_prefix' => substr($api_key, 0, 8) . '...',
                'endpoint' => $request->getPathInfo(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'error' => $error_message,
                'response_time_ms' => $response_time
            ]);
        }
    }

    /**
     * Log successful API request
     *
     * @param Request $request
     * @param $response
     * @param ApiKey $api_key_model
     * @param float $start_time
     */
    private function logSuccessfulRequest(Request $request, $response, ApiKey $api_key_model, float $start_time): void
    {
        $response_time = (int)((microtime(true) - $start_time) * 1000);
        $status_code = $response instanceof JsonResponse ? $response->getStatusCode() : 200;

        ApiUsageLog::logRequest(
            $api_key_model,
            $request->getPathInfo(),
            $request->method(),
            $request->ip(),
            $request->userAgent(),
            $status_code,
            $response_time,
            $this->getRequestData($request),
            $this->getResponseData($response)
        );
    }

    /**
     * Get sanitized request data for logging
     *
     * @param Request $request
     * @return array|null
     */
    private function getRequestData(Request $request): ?array
    {
        $data = [];

        // Include query parameters
        if ($request->query()) {
            $query = $request->query();
            unset($query['api_key']); // Remove API key from logs
            $data['query'] = $query;
        }

        // Include POST data (but sanitize sensitive fields)
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch')) {
            $input = $request->input();
            $sensitive_fields = ['password', 'password_confirmation', 'token', 'api_key'];
            
            foreach ($sensitive_fields as $field) {
                if (isset($input[$field])) {
                    $input[$field] = '[REDACTED]';
                }
            }
            
            $data['body'] = $input;
        }

        return empty($data) ? null : $data;
    }

    /**
     * Get response data sample for logging
     *
     * @param $response
     * @return array|null
     */
    private function getResponseData($response): ?array
    {
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            // Only store a sample for large responses
            if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
                return [
                    'success' => $data['success'] ?? null,
                    'message' => $data['message'] ?? null,
                    'data_count' => count($data['data']),
                    'sample' => array_slice($data['data'], 0, 2) // First 2 items only
                ];
            }
            
            return $data;
        }

        return null;
    }

    /**
     * Add rate limit headers to response
     *
     * @param $response
     * @param array $rate_limit_result
     */
    private function addRateLimitHeaders($response, array $rate_limit_result): void
    {
        if (method_exists($response, 'header')) {
            $response->header('X-RateLimit-Limit', $rate_limit_result['requests_made'] + $rate_limit_result['requests_remaining']);
            $response->header('X-RateLimit-Remaining', $rate_limit_result['requests_remaining']);
            $response->header('X-RateLimit-Reset', $rate_limit_result['reset_time']->timestamp);
        }
    }

    /**
     * Return unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
            'error' => $message
        ], 401);
    }

    /**
     * Return forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Forbidden',
            'error' => $message
        ], 403);
    }

    /**
     * Return rate limit exceeded response
     *
     * @param array $rate_limit_result
     * @return JsonResponse
     */
    private function rateLimitResponse(array $rate_limit_result): JsonResponse
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Rate limit exceeded',
            'error' => 'Too many requests. Please wait before making more requests.',
            'rate_limit' => [
                'requests_made' => $rate_limit_result['requests_made'],
                'requests_remaining' => $rate_limit_result['requests_remaining'],
                'reset_time' => $rate_limit_result['reset_time']->toISOString()
            ]
        ], 429);

        $this->addRateLimitHeaders($response, $rate_limit_result);

        return $response;
    }
}
