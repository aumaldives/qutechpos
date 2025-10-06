<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWoocommerceCustIdToContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if column already exists (for systems that might already have it)
        if (!Schema::hasColumn('contacts', 'woocommerce_cust_id')) {
            Schema::table('contacts', function (Blueprint $table) {
                // Add WooCommerce customer ID column for customer sync
                $table->string('woocommerce_cust_id')->nullable()->after('quickbooks_last_synced_at');
            });
            \Log::info('Added woocommerce_cust_id column to contacts table for customer synchronization');
        } else {
            \Log::info('woocommerce_cust_id column already exists in contacts table');
        }
        
        // Add index if it doesn't exist (safe for all upgrade scenarios)
        if (!$this->indexExists('contacts', 'idx_contacts_business_woo_customer')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->index(['business_id', 'woocommerce_cust_id'], 'idx_contacts_business_woo_customer');
            });
            \Log::info('Added WooCommerce customer index to contacts table');
        } else {
            \Log::info('WooCommerce customer index already exists on contacts table');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Drop the index first (if it exists)
            if ($this->indexExists('contacts', 'idx_contacts_business_woo_customer')) {
                $table->dropIndex('idx_contacts_business_woo_customer');
            }
            
            // Drop the column (if it exists)
            if (Schema::hasColumn('contacts', 'woocommerce_cust_id')) {
                $table->dropColumn('woocommerce_cust_id');
            }
        });
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