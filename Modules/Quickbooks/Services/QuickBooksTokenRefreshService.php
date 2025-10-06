<?php

namespace Modules\Quickbooks\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Quickbooks\Models\QuickbooksLocationSettings;

class QuickBooksTokenRefreshService
{
    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(QuickbooksLocationSettings $locationSettings): bool
    {
        $appConfig = $locationSettings->appConfig();
        if (!$appConfig || !$locationSettings->refresh_token) {
            Log::warning('Cannot refresh token: missing app config or refresh token', [
                'location_id' => $locationSettings->id
            ]);
            return false;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($appConfig->client_id, $appConfig->client_secret)
                ->post($appConfig->getTokenEndpoint(), [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $locationSettings->refresh_token
                ]);

            if (!$response->successful()) {
                Log::error('Token refresh failed', [
                    'location_id' => $locationSettings->id,
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);

                $this->handleRefreshFailure($locationSettings);
                return false;
            }

            $tokenData = $response->json();

            if (!isset($tokenData['access_token'])) {
                Log::error('Invalid token refresh response', [
                    'location_id' => $locationSettings->id,
                    'response' => $tokenData
                ]);
                
                $this->handleRefreshFailure($locationSettings);
                return false;
            }

            // Update tokens
            $locationSettings->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $locationSettings->refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                'last_token_refresh_at' => now(),
                'consecutive_failed_refreshes' => 0,
                'connection_status' => 'connected'
            ]);

            Log::info('Token refreshed successfully', [
                'location_id' => $locationSettings->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Token refresh exception', [
                'location_id' => $locationSettings->id,
                'error' => $e->getMessage()
            ]);

            $this->handleRefreshFailure($locationSettings);
            return false;
        }
    }

    /**
     * Handle token refresh failure
     */
    private function handleRefreshFailure(QuickbooksLocationSettings $locationSettings): void
    {
        $consecutiveFailures = $locationSettings->consecutive_failed_refreshes + 1;
        
        $status = 'token_expired';
        if ($consecutiveFailures >= 5) {
            $status = 'error';
        }

        $locationSettings->update([
            'consecutive_failed_refreshes' => $consecutiveFailures,
            'connection_status' => $status,
            'last_sync_error' => 'Token refresh failed after ' . $consecutiveFailures . ' attempts'
        ]);
    }

    /**
     * Check if token needs refresh and refresh if necessary
     */
    public function refreshIfNeeded(QuickbooksLocationSettings $locationSettings): bool
    {
        if (!$locationSettings->needsTokenRefresh()) {
            return true; // Token is still valid
        }

        return $this->refreshToken($locationSettings);
    }

    /**
     * Add needsTokenRefresh method if it doesn't exist in the model
     */
    public function tokenNeedsRefresh(QuickbooksLocationSettings $locationSettings): bool
    {
        if (!$locationSettings->token_expires_at) {
            return false;
        }
        
        // Refresh if token expires within 10 minutes
        return now()->addMinutes(10)->isAfter($locationSettings->token_expires_at);
    }
}