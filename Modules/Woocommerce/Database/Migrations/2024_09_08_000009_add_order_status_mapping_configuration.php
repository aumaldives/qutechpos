<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddOrderStatusMappingConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add order status mapping configuration to location settings
        Schema::table('woocommerce_location_settings', function (Blueprint $table) {
            // Only add columns if they don't exist yet
            if (!Schema::hasColumn('woocommerce_location_settings', 'order_status_mapping')) {
                $table->json('order_status_mapping')->nullable()->comment('Maps WooCommerce order statuses to POS invoice types');
            }
            
            if (!Schema::hasColumn('woocommerce_location_settings', 'enable_bidirectional_sync')) {
                $table->boolean('enable_bidirectional_sync')->default(true)->comment('Enable two-way order status synchronization');
            }
            
            if (!Schema::hasColumn('woocommerce_location_settings', 'auto_finalize_pos_sales')) {
                $table->boolean('auto_finalize_pos_sales')->default(true)->comment('Auto-finalize POS sales when WooCommerce order is processing/completed');
            }
            
            if (!Schema::hasColumn('woocommerce_location_settings', 'auto_update_woo_status')) {
                $table->boolean('auto_update_woo_status')->default(true)->comment('Auto-update WooCommerce status when POS sale is finalized');
            }
            
            // Webhook secret already exists, skip it
            
            if (!Schema::hasColumn('woocommerce_location_settings', 'enabled_webhook_events')) {
                $table->json('enabled_webhook_events')->nullable()->comment('List of enabled webhook events');
            }
            
            if (!Schema::hasColumn('woocommerce_location_settings', 'default_invoice_type')) {
                $table->string('default_invoice_type', 20)->default('draft')->comment('Default invoice type for new orders');
            }
            
            if (!Schema::hasColumn('woocommerce_location_settings', 'create_draft_on_webhook')) {
                $table->boolean('create_draft_on_webhook')->default(true)->comment('Auto-create draft sales from webhooks');
            }
        });

        // Create order status mapping presets table for easy management
        if (!Schema::hasTable('woocommerce_order_status_presets')) {
            Schema::create('woocommerce_order_status_presets', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->comment('Preset name');
                $table->string('description')->nullable();
                $table->json('mapping_configuration')->comment('Complete status mapping configuration');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['is_active', 'is_default']);
            });

            // Insert default status mapping presets
            DB::table('woocommerce_order_status_presets')->insert([
            [
                'name' => 'Standard Workflow',
                'description' => 'Standard order processing workflow with draft → proforma → final progression',
                'mapping_configuration' => json_encode([
                    'pending' => 'draft',
                    'on-hold' => 'draft', 
                    'processing' => 'proforma',
                    'completed' => 'final',
                    'cancelled' => 'cancelled',
                    'refunded' => 'refunded',
                    'failed' => 'draft'
                ]),
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Immediate Finalization',
                'description' => 'Finalize all orders immediately upon payment',
                'mapping_configuration' => json_encode([
                    'pending' => 'draft',
                    'on-hold' => 'draft',
                    'processing' => 'final',
                    'completed' => 'final', 
                    'cancelled' => 'cancelled',
                    'refunded' => 'refunded',
                    'failed' => 'draft'
                ]),
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Conservative Workflow',
                'description' => 'Keep orders as proforma until manually completed',
                'mapping_configuration' => json_encode([
                    'pending' => 'draft',
                    'on-hold' => 'draft',
                    'processing' => 'proforma',
                    'completed' => 'proforma',
                    'cancelled' => 'cancelled', 
                    'refunded' => 'refunded',
                    'failed' => 'draft'
                ]),
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
            ]);
        }

        // Create webhook event log table for tracking
        if (!Schema::hasTable('woocommerce_webhook_events')) {
            Schema::create('woocommerce_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->string('event_type', 50)->comment('Webhook event type (order.updated, order.created, etc.)');
            $table->string('woocommerce_order_id', 50)->nullable();
            $table->unsignedInteger('pos_transaction_id')->nullable();
            $table->string('status', 20)->default('pending')->comment('processing, completed, failed');
            $table->json('webhook_payload')->nullable()->comment('Original webhook data');
            $table->json('processing_result')->nullable()->comment('Result of processing the webhook');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('pos_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            
            $table->index(['business_id', 'event_type', 'status']);
            $table->index(['woocommerce_order_id', 'status']);
            $table->index(['created_at', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woocommerce_location_settings', function (Blueprint $table) {
            $table->dropColumn([
                'order_status_mapping',
                'enable_bidirectional_sync',
                'auto_finalize_pos_sales',
                'auto_update_woo_status',
                'webhook_secret',
                'enabled_webhook_events',
                'default_invoice_type',
                'create_draft_on_webhook'
            ]);
        });

        Schema::dropIfExists('woocommerce_webhook_events');
        Schema::dropIfExists('woocommerce_order_status_presets');
    }
}