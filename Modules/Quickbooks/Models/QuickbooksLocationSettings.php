<?php

namespace Modules\Quickbooks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Business;
use App\BusinessLocation;
use Modules\Quickbooks\Entities\QuickbooksAppConfig;

class QuickbooksLocationSettings extends Model
{
    protected $fillable = [
        'business_id',
        'location_id',
        'company_id',
        'connection_status',
        'quickbooks_company_name',
        'quickbooks_country',
        'connected_at',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'last_token_refresh_at',
        'sandbox_mode',
        'base_url',
        'is_active',
        'sync_customers',
        'sync_suppliers',
        'sync_products',
        'sync_invoices',
        'sync_payments',
        'sync_purchases',
        'sync_inventory',
        'sync_interval_minutes',
        'enable_auto_sync',
        'sync_mapping_config',
        'last_customer_sync_at',
        'last_supplier_sync_at',
        'last_product_sync_at',
        'last_invoice_sync_at',
        'last_payment_sync_at',
        'last_purchase_sync_at',
        'last_inventory_sync_at',
        'last_successful_sync_at',
        'total_customers_synced',
        'total_suppliers_synced',
        'total_products_synced',
        'total_invoices_synced',
        'total_payments_synced',
        'total_purchases_synced',
        'total_inventory_synced',
        'failed_syncs_count',
        'consecutive_failed_refreshes',
        'last_sync_error',
        'connection_metadata',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_token_refresh_at' => 'datetime',
        'connected_at' => 'datetime',
        'is_active' => 'boolean',
        'sync_customers' => 'boolean',
        'sync_suppliers' => 'boolean',
        'sync_products' => 'boolean',
        'sync_invoices' => 'boolean',
        'sync_payments' => 'boolean',
        'sync_purchases' => 'boolean',
        'sync_inventory' => 'boolean',
        'enable_auto_sync' => 'boolean',
        'sync_mapping_config' => 'array',
        'connection_metadata' => 'array',
        'last_customer_sync_at' => 'datetime',
        'last_supplier_sync_at' => 'datetime',
        'last_product_sync_at' => 'datetime',
        'last_invoice_sync_at' => 'datetime',
        'last_payment_sync_at' => 'datetime',
        'last_purchase_sync_at' => 'datetime',
        'last_inventory_sync_at' => 'datetime',
        'last_successful_sync_at' => 'datetime',
        'total_customers_synced' => 'integer',
        'total_suppliers_synced' => 'integer',
        'total_products_synced' => 'integer',
        'total_invoices_synced' => 'integer',
        'total_payments_synced' => 'integer',
        'total_purchases_synced' => 'integer',
        'total_inventory_synced' => 'integer',
        'failed_syncs_count' => 'integer',
        'consecutive_failed_refreshes' => 'integer',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    protected $hidden = [
        'access_token', 
        'refresh_token'
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    /**
     * Get the app configuration for this connection
     */
    public function appConfig()
    {
        return QuickbooksAppConfig::getActiveConfig($this->sandbox_mode);
    }

    public function isConfigured(): bool
    {
        return $this->connection_status === 'connected' &&
               !empty($this->company_id) && 
               !empty($this->access_token);
    }

    public function isConnected(): bool
    {
        return $this->connection_status === 'connected' &&
               !empty($this->company_id) && 
               !empty($this->access_token) && 
               $this->is_active;
    }

    public function isTokenValid(): bool
    {
        if (!$this->access_token || !$this->token_expires_at) {
            return false;
        }

        return now()->lt($this->token_expires_at->subMinutes(5));
    }

    public function needsTokenRefresh(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        
        // Refresh if token expires within 10 minutes
        return now()->addMinutes(10)->isAfter($this->token_expires_at);
    }

    public function incrementSyncCount(string $type): void
    {
        $field = "total_{$type}_synced";
        if (in_array($field, $this->fillable)) {
            $this->increment($field);
            $this->update(["last_{$type}_sync_at" => now()]);
        }
    }

    public function recordSyncError(string $error): void
    {
        $this->update([
            'last_sync_error' => $error,
            'failed_syncs_count' => $this->failed_syncs_count + 1,
        ]);
    }

    public function recordSuccessfulSync(): void
    {
        $this->update([
            'last_successful_sync_at' => now(),
            'last_sync_error' => null,
        ]);
    }

    public static function findByBusinessAndLocation(int $businessId, int $locationId): ?self
    {
        return self::where('business_id', $businessId)
                   ->where('location_id', $locationId)
                   ->first();
    }

    public static function createForLocation(int $businessId, int $locationId, array $config = []): self
    {
        return self::create(array_merge([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'sandbox_mode' => 'sandbox',
            'sync_interval_minutes' => 60,
            'enable_auto_sync' => false,
        ], $config));
    }

    public function getSyncStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if (!$this->isConfigured()) {
            return 'not_configured';
        }

        if (!$this->isTokenValid()) {
            return 'token_expired';
        }

        if ($this->failed_syncs_count > 5) {
            return 'error';
        }

        if ($this->last_successful_sync_at && $this->last_successful_sync_at->diffInHours() > 24) {
            return 'stale';
        }

        return 'active';
    }

    public function getNextSyncTimeAttribute(): ?\Carbon\Carbon
    {
        if (!$this->enable_auto_sync || !$this->last_successful_sync_at) {
            return null;
        }

        return $this->last_successful_sync_at->addMinutes($this->sync_interval_minutes);
    }

    /**
     * Get the OAuth authorization URL for this location
     */
    public function getAuthorizationUrl()
    {
        $appConfig = $this->appConfig();
        if (!$appConfig) {
            throw new \Exception('QuickBooks app configuration not found');
        }

        $state = $this->generateOAuthState();
        
        return $appConfig->getAuthorizationUrl($state);
    }

    /**
     * Generate OAuth state parameter that includes location identification
     */
    private function generateOAuthState()
    {
        $stateData = [
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16))
        ];
        
        return base64_encode(json_encode($stateData));
    }

    /**
     * Validate and decode OAuth state parameter
     */
    public static function validateOAuthState($state)
    {
        try {
            $decoded = json_decode(base64_decode($state), true);
            
            if (!$decoded || !isset($decoded['business_id'], $decoded['location_id'])) {
                return null;
            }
            
            // Check if state is not too old (1 hour max)
            if ((time() - $decoded['timestamp']) > 3600) {
                return null;
            }
            
            return $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update connection status based on current state
     */
    public function updateConnectionStatus()
    {
        $status = 'disconnected';
        
        if (!empty($this->company_id) && !empty($this->access_token)) {
            if ($this->isTokenValid()) {
                $status = 'connected';
            } else {
                $status = 'token_expired';
            }
        }
        
        $this->connection_status = $status;
        return $this;
    }
}