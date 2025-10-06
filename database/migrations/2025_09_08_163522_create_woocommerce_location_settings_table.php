<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_location_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            
            // API Configuration
            $table->text('woocommerce_app_url')->nullable();
            $table->text('woocommerce_consumer_key')->nullable();
            $table->text('woocommerce_consumer_secret')->nullable();
            
            // Webhook Configuration
            $table->text('webhook_url')->nullable();
            $table->string('webhook_secret', 255)->nullable();
            $table->json('webhook_events')->nullable(); // ['product.created', 'order.created', etc.]
            
            // Sync Settings
            $table->boolean('enable_auto_sync')->default(true);
            $table->integer('sync_interval_minutes')->default(15);
            $table->timestamp('last_product_sync_at')->nullable();
            $table->timestamp('last_order_sync_at')->nullable();
            $table->timestamp('last_inventory_sync_at')->nullable();
            
            // Sync Statistics
            $table->integer('total_products_synced')->default(0);
            $table->integer('total_orders_synced')->default(0);
            $table->integer('total_customers_synced')->default(0);
            $table->integer('failed_syncs_count')->default(0);
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->text('last_sync_error')->nullable();
            
            // Status and Control
            $table->boolean('is_active')->default(true);
            $table->boolean('sync_products')->default(true);
            $table->boolean('sync_orders')->default(true);
            $table->boolean('sync_inventory')->default(true);
            $table->boolean('sync_customers')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            
            // Indexes
            $table->index(['business_id', 'location_id']);
            $table->index(['business_id', 'is_active']);
            $table->unique(['business_id', 'location_id'], 'unique_business_location_woo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_location_settings');
    }
};
