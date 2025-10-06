<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbooksLocationSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quickbooks_location_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            
            // QuickBooks API Configuration
            $table->string('company_id')->nullable()->comment('QuickBooks Company ID');
            $table->string('client_id')->nullable()->comment('QuickBooks App Client ID');
            $table->string('client_secret')->nullable()->comment('QuickBooks App Client Secret');
            $table->text('access_token')->nullable()->comment('OAuth2 Access Token');
            $table->text('refresh_token')->nullable()->comment('OAuth2 Refresh Token');
            $table->timestamp('token_expires_at')->nullable();
            $table->string('sandbox_mode')->default('sandbox')->comment('sandbox or production');
            $table->string('base_url')->nullable()->comment('QuickBooks API Base URL');
            
            // Sync Configuration
            $table->boolean('is_active')->default(false);
            $table->boolean('sync_customers')->default(true);
            $table->boolean('sync_suppliers')->default(true);
            $table->boolean('sync_products')->default(true);
            $table->boolean('sync_invoices')->default(true);
            $table->boolean('sync_payments')->default(true);
            $table->boolean('sync_purchases')->default(true);
            $table->boolean('sync_inventory')->default(true);
            
            // Sync Settings
            $table->integer('sync_interval_minutes')->default(60)->comment('Auto sync interval');
            $table->boolean('enable_auto_sync')->default(false);
            $table->json('sync_mapping_config')->nullable()->comment('Field mapping configuration');
            
            // Sync Statistics
            $table->timestamp('last_customer_sync_at')->nullable();
            $table->timestamp('last_supplier_sync_at')->nullable();
            $table->timestamp('last_product_sync_at')->nullable();
            $table->timestamp('last_invoice_sync_at')->nullable();
            $table->timestamp('last_payment_sync_at')->nullable();
            $table->timestamp('last_purchase_sync_at')->nullable();
            $table->timestamp('last_inventory_sync_at')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            
            $table->integer('total_customers_synced')->default(0);
            $table->integer('total_suppliers_synced')->default(0);
            $table->integer('total_products_synced')->default(0);
            $table->integer('total_invoices_synced')->default(0);
            $table->integer('total_payments_synced')->default(0);
            $table->integer('total_purchases_synced')->default(0);
            $table->integer('total_inventory_synced')->default(0);
            $table->integer('failed_syncs_count')->default(0);
            
            $table->text('last_sync_error')->nullable();
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            
            // Indexes
            $table->unique(['business_id', 'location_id']);
            $table->index(['is_active', 'enable_auto_sync']);
            $table->index(['last_successful_sync_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quickbooks_location_settings');
    }
}