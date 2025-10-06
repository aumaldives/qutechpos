<?php

namespace Modules\Woocommerce\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WoocommerceLocationSetting extends Model
{
    use HasFactory;

    protected $table = 'woocommerce_location_settings';

    protected $fillable = [
        'business_id',
        'location_id',
        'woocommerce_app_url',
        'woocommerce_consumer_key', 
        'woocommerce_consumer_secret',
        'webhook_url',
        'webhook_secret',
        'webhook_events',
        'enable_auto_sync',
        'sync_interval_minutes',
        'last_product_sync_at',
        'last_order_sync_at',
        'last_inventory_sync_at',
        'total_products_synced',
        'total_orders_synced',
        'total_customers_synced',
        'total_inventory_synced',
        'failed_syncs_count',
        'last_successful_sync_at',
        'last_sync_error',
        'is_active',
        'sync_products',
        'sync_orders',
        'sync_inventory',
        'sync_customers',
        'order_status_mapping',
        'enable_bidirectional_sync',
        'auto_finalize_pos_sales',
        'auto_update_woo_status',
        'create_draft_on_webhook'
    ];

    protected $casts = [
        'webhook_events' => 'array',
        'enable_auto_sync' => 'boolean',
        'is_active' => 'boolean',
        'sync_products' => 'boolean',
        'sync_orders' => 'boolean',
        'sync_inventory' => 'boolean',
        'sync_customers' => 'boolean',
        'order_status_mapping' => 'array',
        'enable_bidirectional_sync' => 'boolean',
        'auto_finalize_pos_sales' => 'boolean',
        'auto_update_woo_status' => 'boolean',
        'create_draft_on_webhook' => 'boolean',
        'last_product_sync_at' => 'datetime',
        'last_order_sync_at' => 'datetime',
        'last_inventory_sync_at' => 'datetime',
        'last_successful_sync_at' => 'datetime'
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    public function businessLocation()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    /**
     * Get WooCommerce settings for a specific business location
     */
    public static function getByLocation($business_id, $location_id)
    {
        return self::where('business_id', $business_id)
                   ->where('location_id', $location_id)
                   ->first();
    }

    /**
     * Get all active WooCommerce locations for a business
     */
    public static function getActiveForBusiness($business_id)
    {
        return self::where('business_id', $business_id)
                   ->where('is_active', true)
                   ->with('businessLocation')
                   ->get();
    }

    /**
     * Check if API configuration is complete
     */
    public function hasValidApiConfig()
    {
        return !empty($this->woocommerce_app_url) &&
               !empty($this->woocommerce_consumer_key) &&
               !empty($this->woocommerce_consumer_secret);
    }

    /**
     * Get WooCommerce client instance for this location
     */
    public function getWooCommerceClient()
    {
        if (!$this->hasValidApiConfig()) {
            return null;
        }

        return new \Automattic\WooCommerce\Client(
            rtrim($this->woocommerce_app_url, '/'),
            $this->woocommerce_consumer_key,
            $this->woocommerce_consumer_secret,
            [
                'version' => 'wc/v3',
                'verify_ssl' => false
            ]
        );
    }

    /**
     * Update sync statistics
     */
    public function updateSyncStats($type, $count = 1, $success = true)
    {
        $data = [];
        
        if ($success) {
            switch ($type) {
                case 'products':
                    $data['total_products_synced'] = $this->total_products_synced + $count;
                    $data['last_product_sync_at'] = now();
                    break;
                case 'orders':
                    $data['total_orders_synced'] = $this->total_orders_synced + $count;
                    $data['last_order_sync_at'] = now();
                    break;
                case 'customers':
                    $data['total_customers_synced'] = $this->total_customers_synced + $count;
                    break;
                case 'inventory':
                    $data['total_inventory_synced'] = $this->total_inventory_synced + $count;
                    $data['last_inventory_sync_at'] = now();
                    break;
            }
            $data['last_successful_sync_at'] = now();
            $data['last_sync_error'] = null;
        } else {
            $data['failed_syncs_count'] = $this->failed_syncs_count + 1;
        }

        $this->update($data);
    }

    /**
     * Log sync error
     */
    public function logSyncError($error_message)
    {
        $this->update([
            'last_sync_error' => $error_message,
            'failed_syncs_count' => $this->failed_syncs_count + 1
        ]);
    }

    /**
     * Check if location needs sync based on interval
     */
    public function needsSync()
    {
        if (!$this->is_active || !$this->enable_auto_sync) {
            return false;
        }

        if (is_null($this->last_successful_sync_at)) {
            return true;
        }

        $next_sync_time = $this->last_successful_sync_at->addMinutes($this->sync_interval_minutes);
        return now()->gte($next_sync_time);
    }

    /**
     * Get sync health status
     */
    public function getSyncHealthStatus()
    {
        if (!$this->is_active) {
            return 'disabled';
        }

        if (!$this->hasValidApiConfig()) {
            return 'no_config';
        }

        if ($this->failed_syncs_count > 5) {
            return 'failing';
        }

        if (is_null($this->last_successful_sync_at)) {
            return 'never_synced';
        }

        $hours_since_sync = $this->last_successful_sync_at->diffInHours(now());
        if ($hours_since_sync > 24) {
            return 'stale';
        }

        return 'healthy';
    }
}