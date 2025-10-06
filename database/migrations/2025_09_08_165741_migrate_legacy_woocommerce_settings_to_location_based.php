<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if woocommerce_location_settings table exists
        if (!Schema::hasTable('woocommerce_location_settings')) {
            return; // Skip if location settings table doesn't exist yet
        }

        // Get all businesses with existing WooCommerce settings
        $businesses = DB::table('business')
            ->whereNotNull('woocommerce_api_settings')
            ->where('woocommerce_api_settings', '!=', '')
            ->get();

        foreach ($businesses as $business) {
            $wooSettings = json_decode($business->woocommerce_api_settings, true);
            
            if (empty($wooSettings) || empty($wooSettings['woocommerce_app_url'])) {
                continue; // Skip invalid settings
            }

            // Get the default location for this business or the first available location
            $defaultLocation = DB::table('business_locations')
                ->where('business_id', $business->id)
                ->orderBy('id')
                ->first();

            if (!$defaultLocation) {
                continue; // Skip if no locations found
            }

            // Check if location already has WooCommerce configuration
            $existingConfig = DB::table('woocommerce_location_settings')
                ->where('business_id', $business->id)
                ->where('location_id', $defaultLocation->id)
                ->first();

            if ($existingConfig) {
                continue; // Skip if already configured
            }

            // Migrate settings to location-based configuration
            try {
                DB::table('woocommerce_location_settings')->insert([
                    'business_id' => $business->id,
                    'location_id' => $defaultLocation->id,
                    'woocommerce_app_url' => $wooSettings['woocommerce_app_url'] ?? '',
                    'woocommerce_consumer_key' => $wooSettings['woocommerce_consumer_key'] ?? '',
                    'woocommerce_consumer_secret' => $wooSettings['woocommerce_consumer_secret'] ?? '',
                    'webhook_url' => url('modules/woocommerce/webhook/' . $defaultLocation->id),
                    'webhook_secret' => bin2hex(random_bytes(32)),
                    'webhook_events' => json_encode(['order.created', 'order.updated', 'order.deleted']),
                    'enable_auto_sync' => true,
                    'sync_interval_minutes' => 15,
                    'is_active' => true,
                    'sync_products' => true,
                    'sync_orders' => true,
                    'sync_inventory' => true,
                    'sync_customers' => true,
                    'total_products_synced' => 0,
                    'total_orders_synced' => 0,
                    'total_customers_synced' => 0,
                    'failed_syncs_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                echo "Migrated WooCommerce settings for business {$business->id} to location {$defaultLocation->id}\n";

            } catch (Exception $e) {
                echo "Failed to migrate settings for business {$business->id}: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove migrated location-based configurations
        // This allows rolling back the migration if needed
        DB::table('woocommerce_location_settings')->truncate();
    }
};
