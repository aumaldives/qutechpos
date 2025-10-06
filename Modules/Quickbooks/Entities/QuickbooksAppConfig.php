<?php

namespace Modules\Quickbooks\Entities;

use Illuminate\Database\Eloquent\Model;

class QuickbooksAppConfig extends Model
{
    protected $table = 'quickbooks_app_config';
    
    protected $fillable = [
        'environment',
        'client_id', 
        'client_secret',
        'discovery_document_url',
        'oauth_redirect_uri',
        'is_active',
        'scopes',
        'webhook_verifier_token'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scopes' => 'array',
        'client_secret' => 'encrypted', // Automatically encrypt/decrypt
    ];

    /**
     * Get the active configuration for the specified environment
     */
    public static function getActiveConfig($environment = null)
    {
        $environment = $environment ?? config('quickbooks.default_environment', 'sandbox');
        
        return self::where('environment', $environment)
                   ->where('is_active', true)
                   ->first();
    }

    /**
     * Get the OAuth authorization URL
     */
    public function getAuthorizationUrl($state = null, $scope = null)
    {
        // Note: QuickBooks uses the same OAuth URL for both environments
        $baseUrl = 'https://appcenter.intuit.com/connect/oauth2';
            
        $scopes = $scope ?? $this->getDefaultScopes();
        
        $params = [
            'client_id' => $this->client_id,
            'scope' => implode(' ', $scopes),
            'redirect_uri' => $this->oauth_redirect_uri,
            'response_type' => 'code',
            'access_type' => 'offline',
            'state' => $state ?? $this->generateState()
        ];

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Get default OAuth scopes for QuickBooks integration
     */
    public function getDefaultScopes()
    {
        return $this->scopes ?? [
            'com.intuit.quickbooks.accounting'
        ];
    }

    /**
     * Generate a secure state parameter for OAuth
     */
    private function generateState()
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Get the token endpoint URL
     */
    public function getTokenEndpoint()
    {
        // Note: QuickBooks uses the same token endpoint for both environments
        return 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    }

    /**
     * Get the QuickBooks API base URL
     */
    public function getApiBaseUrl()
    {
        return $this->environment === 'production'
            ? 'https://quickbooks-api.intuit.com'
            : 'https://sandbox-quickbooks.api.intuit.com';
    }

    /**
     * Check if the configuration is valid
     */
    public function isValid()
    {
        return !empty($this->client_id) && 
               !empty($this->client_secret) && 
               !empty($this->oauth_redirect_uri) && 
               $this->is_active;
    }

    /**
     * Get webhook configuration
     */
    public function getWebhookConfig()
    {
        return [
            'verifier_token' => $this->webhook_verifier_token,
            'endpoint_url' => route('quickbooks.webhook.handler')
        ];
    }
}