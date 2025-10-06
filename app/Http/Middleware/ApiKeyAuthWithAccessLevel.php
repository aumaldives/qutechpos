<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\ApiKey;
use Illuminate\Http\JsonResponse;

class ApiKeyAuthWithAccessLevel
{
    /**
     * Handle an incoming request with access level checking
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\JsonResponse)  $next
     * @param  string|null  $accessLevel
     * @param  string|null  $abilities
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next, ?string $accessLevel = null, ?string $abilities = null)
    {
        $token = $this->extractApiKey($request);

        if (!$token) {
            return $this->unauthorizedResponse('API key is required');
        }

        $apiKey = ApiKey::findValidKey($token);

        if (!$apiKey) {
            return $this->unauthorizedResponse('Invalid or expired API key');
        }

        // Check access level if specified
        if ($accessLevel && !$apiKey->hasAccessLevel($accessLevel)) {
            return $this->forbiddenResponse("Insufficient access level. Required: {$accessLevel}");
        }

        // Check abilities if specified
        if ($abilities) {
            $requiredAbilities = explode(',', $abilities);
            foreach ($requiredAbilities as $ability) {
                if (!$apiKey->hasAbility(trim($ability))) {
                    return $this->forbiddenResponse("Missing required ability: " . trim($ability));
                }
            }
        }

        // Update last used
        $apiKey->markAsUsed();

        // Add API key to request for use in controllers
        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('authenticated_user', $apiKey->user);

        return $next($request);
    }

    /**
     * Extract API key from request
     */
    private function extractApiKey(Request $request): ?string
    {
        // Check Authorization header (Bearer token)
        if ($request->hasHeader('Authorization')) {
            $authHeader = $request->header('Authorization');
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return trim($matches[1]);
            }
        }

        // Check X-API-Key header
        if ($request->hasHeader('X-API-Key')) {
            return $request->header('X-API-Key');
        }

        // Check query parameter
        return $request->query('api_key');
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED'
        ], 401);
    }

    /**
     * Return forbidden response
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'FORBIDDEN'
        ], 403);
    }
}