<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWoocommerceApiSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create modern API settings table to replace legacy JSON column
        Schema::create('woocommerce_api_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->string('store_url');
            $table->string('consumer_key');
            $table->string('consumer_secret');
            $table->string('api_version', 10)->default('v3');
            $table->boolean('auto_sync_enabled')->default(false);
            $table->string('webhook_secret')->nullable();
            $table->timestamp('webhook_secret_rotated_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_settings')->nullable(); // Modern JSON settings
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            
            // Performance indexes
            $table->index(['business_id', 'is_active'], 'idx_business_active');
            $table->index(['business_id', 'auto_sync_enabled'], 'idx_business_auto_sync');
            $table->index(['last_sync_at'], 'idx_last_sync');
        });
        
        // Create performance metrics table
        Schema::create('woocommerce_performance_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->json('metrics');
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            // Index for time-based queries
            $table->index(['recorded_at'], 'idx_recorded_at');
        });
        
        // Create webhook logs table
        Schema::create('woocommerce_webhook_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('business_id')->unsigned();
            $table->string('webhook_type', 50);
            $table->string('event_id')->nullable();
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Foreign key and indexes
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index(['business_id', 'status'], 'idx_business_status');
            $table->index(['webhook_type'], 'idx_webhook_type');
            $table->index(['event_id'], 'idx_event_id');
            $table->index(['created_at', 'status'], 'idx_created_status');
        });

        // Migrate data from legacy woocommerce_api_settings JSON column if it exists
        $this->migrateLegacyApiSettings();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_webhook_logs');
        Schema::dropIfExists('woocommerce_performance_metrics');
        Schema::dropIfExists('woocommerce_api_settings');
    }
    
    /**
     * Migrate legacy JSON settings to new table structure
     */
    private function migrateLegacyApiSettings()
    {
        try {
            // Check if legacy column exists
            if (!Schema::hasColumn('business', 'woocommerce_api_settings')) {
                return; // No legacy data to migrate
            }
            
            $businesses = \DB::table('business')
                ->whereNotNull('woocommerce_api_settings')
                ->get(['id', 'woocommerce_api_settings']);
            
            foreach ($businesses as $business) {
                $settings = json_decode($business->woocommerce_api_settings, true);
                
                if (empty($settings)) continue;
                
                // Create new API settings record
                \DB::table('woocommerce_api_settings')->insert([
                    'business_id' => $business->id,
                    'store_url' => $settings['woocommerce_api_url'] ?? '',
                    'consumer_key' => $settings['woocommerce_api_consumer_key'] ?? '',
                    'consumer_secret' => $settings['woocommerce_api_consumer_secret'] ?? '',
                    'api_version' => 'v3', // Upgrade all to v3
                    'auto_sync_enabled' => !empty($settings['enable_auto_sync']),
                    'webhook_secret' => $settings['woocommerce_wh_oc_secret'] ?? null,
                    'last_sync_at' => null,
                    'sync_settings' => json_encode([
                        'sync_products' => true,
                        'sync_orders' => true,
                        'sync_categories' => true,
                        'batch_size' => 50,
                        'sync_images' => true
                    ]),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                \Log::info("Migrated WooCommerce settings for business {$business->id}");
            }
            
            \Log::info("WooCommerce API settings migration completed successfully");
            
        } catch (\Exception $e) {
            \Log::error('Failed to migrate legacy WooCommerce API settings', [
                'error' => $e->getMessage()
            ]);
        }
    }
}