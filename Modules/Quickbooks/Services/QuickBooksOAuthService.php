<?php

namespace Modules\Quickbooks\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Quickbooks\Models\QuickbooksLocationSettings;

class QuickBooksOAuthService
{
    private Client $httpClient;
    private bool $isSandbox;
    private string $discoveryUrl;
    private string $redirectUri;

    public function __construct(bool $isSandbox = true)
    {
        $this->isSandbox = $isSandbox;
        $this->httpClient = new Client(['timeout' => 30]);
        
        $this->discoveryUrl = $this->isSandbox 
            ? 'https://appcenter.intuit.com/connect/oauth2'
            : 'https://appcenter.intuit.com/connect/oauth2';
            
        $this->redirectUri = url('/quickbooks/oauth/callback');
    }

    public function getAuthorizationUrl(int $businessId, int $locationId, string $clientId): string
    {
        $state = $this->generateState($businessId, $locationId);
        
        $params = http_build_query([
            'client_id' => $clientId,
            'scope' => 'com.intuit.quickbooks.accounting',
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'access_type' => 'offline',
            'state' => $state,
        ]);

        return $this->discoveryUrl . '?' . $params;
    }

    public function handleCallback(
        string $code, 
        string $state, 
        string $realmId, 
        string $clientId, 
        string $clientSecret
    ): array {
        try {
            $stateData = $this->parseState($state);
            
            $tokenData = $this->exchangeCodeForTokens($code, $clientId, $clientSecret);
            
            $settings = QuickbooksLocationSettings::findByBusinessAndLocation(
                $stateData['business_id'], 
                $stateData['location_id']
            );

            if (!$settings) {
                $settings = QuickbooksLocationSettings::createForLocation(
                    $stateData['business_id'],
                    $stateData['location_id']
                );
            }

            $settings->update([
                'company_id' => $realmId,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                'is_active' => true,
            ]);

            return [
                'success' => true,
                'message' => 'QuickBooks connection established successfully',
                'settings' => $settings,
            ];

        } catch (Exception $e) {
            Log::error('QuickBooks OAuth callback failed', [
                'code' => $code,
                'state' => $state,
                'realm_id' => $realmId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to connect to QuickBooks: ' . $e->getMessage(),
            ];
        }
    }

    private function exchangeCodeForTokens(string $code, string $clientId, string $clientSecret): array
    {
        $tokenUrl = $this->isSandbox 
            ? 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer'
            : 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';

        $response = $this->httpClient->post($tokenUrl, [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
            ],
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        $tokenData = json_decode($response->getBody()->getContents(), true);

        if (!isset($tokenData['access_token'])) {
            throw new Exception('Failed to obtain access token from QuickBooks');
        }

        return $tokenData;
    }

    private function generateState(int $businessId, int $locationId): string
    {
        $data = [
            'business_id' => $businessId,
            'location_id' => $locationId,
            'timestamp' => time(),
            'nonce' => Str::random(32),
        ];

        return base64_encode(json_encode($data));
    }

    private function parseState(string $state): array
    {
        try {
            $decoded = base64_decode($state);
            $data = json_decode($decoded, true);

            if (!$data || !isset($data['business_id'], $data['location_id'])) {
                throw new Exception('Invalid state parameter');
            }

            if (time() - $data['timestamp'] > 600) {
                throw new Exception('State parameter expired');
            }

            return $data;

        } catch (Exception $e) {
            throw new Exception('Invalid or expired state parameter');
        }
    }

    public function revokeConnection(QuickbooksLocationSettings $settings): bool
    {
        try {
            $revokeUrl = $this->isSandbox 
                ? 'https://developer.api.intuit.com/v2/oauth2/tokens/revoke'
                : 'https://developer.api.intuit.com/v2/oauth2/tokens/revoke';

            $this->httpClient->post($revokeUrl, [
                'form_params' => [
                    'token' => $settings->refresh_token,
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($settings->client_id . ':' . $settings->client_secret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $settings->update([
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
                'is_active' => false,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('QuickBooks connection revocation failed', [
                'business_id' => $settings->business_id,
                'location_id' => $settings->location_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function refreshToken(QuickbooksLocationSettings $settings): bool
    {
        try {
            $tokenUrl = $this->isSandbox 
                ? 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer'
                : 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';

            $response = $this->httpClient->post($tokenUrl, [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $settings->refresh_token,
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($settings->client_id . ':' . $settings->client_secret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $tokenData = json_decode($response->getBody()->getContents(), true);

            if (isset($tokenData['access_token'])) {
                $settings->update([
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? $settings->refresh_token,
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                ]);

                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('QuickBooks token refresh failed', [
                'business_id' => $settings->business_id,
                'location_id' => $settings->location_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}