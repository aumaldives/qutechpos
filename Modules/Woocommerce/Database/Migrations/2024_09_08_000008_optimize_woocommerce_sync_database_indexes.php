<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class OptimizeWoocommerceSyncDatabaseIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Optimize products table for WooCommerce sync operations
        Schema::table('products', function (Blueprint $table) {
            // Composite index for sync operations
            $table->index(['business_id', 'woocommerce_product_id'], 'idx_products_business_woo_sync');
            
            // Index for sync status and timestamps
            $table->index(['business_id', 'updated_at'], 'idx_products_business_updated');
            
            // Index for active products sync
            $table->index(['business_id', 'enable_stock', 'type'], 'idx_products_business_stock_type');
        });

        // Optimize variations table for sync operations
        Schema::table('variations', function (Blueprint $table) {
            // Composite index for variation sync
            $table->index(['product_id', 'woocommerce_variation_id'], 'idx_variations_product_woo_sync');
            
            // Index for variation lookups
            $table->index(['product_id', 'name'], 'idx_variations_product_name');
        });

        // Optimize variation_location_details for inventory sync
        Schema::table('variation_location_details', function (Blueprint $table) {
            // Composite index for inventory sync operations
            $table->index(['location_id', 'variation_id', 'qty_available'], 'idx_vld_location_variation_qty');
            
            // Index for stock level queries
            $table->index(['location_id', 'product_id', 'qty_available'], 'idx_vld_location_product_qty');
            
            // Index for low stock alerts
            $table->index(['location_id', 'qty_available'], 'idx_vld_location_qty');
        });

        // Optimize transactions for order sync
        Schema::table('transactions', function (Blueprint $table) {
            // Composite index for WooCommerce order sync
            $table->index(['business_id', 'woocommerce_order_id'], 'idx_transactions_business_woo_order');
            
            // Index for sync status and location
            $table->index(['business_id', 'location_id', 'type'], 'idx_transactions_business_location_type');
            
            // Index for order status and date range queries
            $table->index(['business_id', 'status', 'transaction_date'], 'idx_transactions_business_status_date');
        });

        // Optimize contacts for customer sync
        Schema::table('contacts', function (Blueprint $table) {
            // WooCommerce customer index - only create if column exists and index doesn't exist
            if (Schema::hasColumn('contacts', 'woocommerce_cust_id') && !$this->indexExists('contacts', 'idx_contacts_business_woo_customer')) {
                $table->index(['business_id', 'woocommerce_cust_id'], 'idx_contacts_business_woo_customer');
            }
            
            // Index for customer lookup by email
            if (!$this->indexExists('contacts', 'idx_contacts_business_email')) {
                $table->index(['business_id', 'email'], 'idx_contacts_business_email');
            }
            
            // Index for customer type and status
            if (!$this->indexExists('contacts', 'idx_contacts_business_type_default')) {
                $table->index(['business_id', 'type', 'is_default'], 'idx_contacts_business_type_default');
            }
        });

        // Optimize WooCommerce sync executions table
        Schema::table('woocommerce_sync_executions', function (Blueprint $table) {
            // Composite index for execution tracking
            $table->index(['business_id', 'location_id', 'status'], 'idx_woo_exec_business_location_status');
            
            // Index for execution history and cleanup
            $table->index(['business_id', 'created_at', 'status'], 'idx_woo_exec_business_created_status');
            
            // Index for active executions monitoring
            $table->index(['status', 'started_at'], 'idx_woo_exec_status_started');
        });

        // Optimize WooCommerce sync progress table
        Schema::table('woocommerce_sync_progress', function (Blueprint $table) {
            // Index for progress monitoring
            $table->index(['business_id', 'location_id', 'status'], 'idx_woo_progress_business_location_status');
            
            // Index for cleanup of completed syncs
            $table->index(['status', 'completed_at'], 'idx_woo_progress_status_completed');
        });

        // Optimize WooCommerce sync schedules table
        Schema::table('woocommerce_sync_schedules', function (Blueprint $table) {
            // Index for active schedules execution
            $table->index(['business_id', 'location_id', 'is_active'], 'idx_woo_schedules_business_location_active');
            
            // Index for schedule execution timing
            $table->index(['is_active', 'next_run_at'], 'idx_woo_schedules_active_next_run');
            
            // Index for schedule management
            $table->index(['business_id', 'sync_type', 'is_active'], 'idx_woo_schedules_business_type_active');
        });

        // Add database-specific optimizations
        $this->addDatabaseSpecificOptimizations();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove indexes from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_business_woo_sync');
            $table->dropIndex('idx_products_business_updated');
            $table->dropIndex('idx_products_business_stock_type');
        });

        // Remove indexes from variations table
        Schema::table('variations', function (Blueprint $table) {
            $table->dropIndex('idx_variations_product_woo_sync');
            $table->dropIndex('idx_variations_product_name');
        });

        // Remove indexes from variation_location_details table
        Schema::table('variation_location_details', function (Blueprint $table) {
            $table->dropIndex('idx_vld_location_variation_qty');
            $table->dropIndex('idx_vld_location_product_qty');
            $table->dropIndex('idx_vld_location_qty');
        });

        // Remove indexes from transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_business_woo_order');
            $table->dropIndex('idx_transactions_business_location_type');
            $table->dropIndex('idx_transactions_business_status_date');
        });

        // Remove indexes from contacts table
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('idx_contacts_business_woo_customer');
            $table->dropIndex('idx_contacts_business_email');
            $table->dropIndex('idx_contacts_business_type_default');
        });

        // Remove indexes from WooCommerce sync executions table
        Schema::table('woocommerce_sync_executions', function (Blueprint $table) {
            $table->dropIndex('idx_woo_exec_business_location_status');
            $table->dropIndex('idx_woo_exec_business_created_status');
            $table->dropIndex('idx_woo_exec_status_started');
        });

        // Remove indexes from WooCommerce sync progress table
        Schema::table('woocommerce_sync_progress', function (Blueprint $table) {
            $table->dropIndex('idx_woo_progress_business_location_status');
            $table->dropIndex('idx_woo_progress_status_completed');
        });

        // Remove indexes from WooCommerce sync schedules table
        Schema::table('woocommerce_sync_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_woo_schedules_business_location_active');
            $table->dropIndex('idx_woo_schedules_active_next_run');
            $table->dropIndex('idx_woo_schedules_business_type_active');
        });
    }

    /**
     * Add database-specific optimizations
     */
    private function addDatabaseSpecificOptimizations()
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        switch ($driver) {
            case 'mysql':
                $this->addMySQLOptimizations();
                break;
            case 'pgsql':
                $this->addPostgreSQLOptimizations();
                break;
        }
    }

    /**
     * Add MySQL-specific optimizations
     */
    private function addMySQLOptimizations()
    {
        try {
            // Optimize table storage engines and settings for sync tables
            DB::statement('ALTER TABLE woocommerce_sync_executions ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
            DB::statement('ALTER TABLE woocommerce_sync_progress ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
            DB::statement('ALTER TABLE woocommerce_sync_schedules ENGINE=InnoDB');
            
            // Add full-text search for error messages (if supported)
            DB::statement('ALTER TABLE woocommerce_sync_executions ADD FULLTEXT(error_message)');
            
            // Optimize for frequent INSERT/UPDATE operations
            DB::statement('ALTER TABLE woocommerce_sync_progress MODIFY metadata JSON COMPRESSION=\'zlib\'');

        } catch (\Exception $e) {
            // Log but don't fail migration for MySQL-specific optimizations
            \Log::warning('MySQL optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Add PostgreSQL-specific optimizations
     */
    private function addPostgreSQLOptimizations()
    {
        try {
            // Create partial indexes for active records only (PostgreSQL feature)
            DB::statement('CREATE INDEX CONCURRENTLY idx_woo_exec_active_partial 
                          ON woocommerce_sync_executions (business_id, location_id, created_at) 
                          WHERE status IN (\'dispatched\', \'processing\')');
            
            DB::statement('CREATE INDEX CONCURRENTLY idx_woo_progress_active_partial 
                          ON woocommerce_sync_progress (business_id, location_id, started_at) 
                          WHERE status IN (\'processing\', \'paused\')');

            // Create expression indexes for JSON fields
            DB::statement('CREATE INDEX CONCURRENTLY idx_woo_schedules_next_run_expr 
                          ON woocommerce_sync_schedules ((metadata->>\'next_run_calculated\')) 
                          WHERE is_active = true');

        } catch (\Exception $e) {
            // Log but don't fail migration for PostgreSQL-specific optimizations
            \Log::warning('PostgreSQL optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $indexName)
    {
        try {
            $indexes = \DB::select("SHOW INDEX FROM $table WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}