<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddWoocommercePerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            // Add performance indexes for WooCommerce sync operations
            
            // Products table - optimize WooCommerce product lookups
            if (Schema::hasTable('products')) {
                Schema::table('products', function (Blueprint $table) {
                    // Check if index already exists before adding
                    if (!$this->indexExists('products', 'idx_woocommerce_sync')) {
                        $table->index(['business_id', 'woocommerce_product_id', 'woocommerce_disable_sync'], 'idx_woocommerce_sync');
                    }
                    
                    if (!$this->indexExists('products', 'idx_woocommerce_product_id')) {
                        $table->index(['woocommerce_product_id'], 'idx_woocommerce_product_id');
                    }
                    
                    if (!$this->indexExists('products', 'idx_updated_at_sync')) {
                        $table->index(['updated_at', 'business_id'], 'idx_updated_at_sync');
                    }
                });
            }
            
            // Categories table - optimize category sync lookups
            if (Schema::hasTable('categories')) {
                Schema::table('categories', function (Blueprint $table) {
                    if (!$this->indexExists('categories', 'idx_woocommerce_cat')) {
                        $table->index(['business_id', 'woocommerce_cat_id'], 'idx_woocommerce_cat');
                    }
                    
                    if (!$this->indexExists('categories', 'idx_woocommerce_cat_id')) {
                        $table->index(['woocommerce_cat_id'], 'idx_woocommerce_cat_id');
                    }
                    
                    if (!$this->indexExists('categories', 'idx_category_sync')) {
                        $table->index(['business_id', 'category_type', 'parent_id'], 'idx_category_sync');
                    }
                });
            }
            
            // Variations table - optimize variation sync
            if (Schema::hasTable('variations')) {
                Schema::table('variations', function (Blueprint $table) {
                    if (!$this->indexExists('variations', 'idx_woocommerce_variation')) {
                        $table->index(['product_id', 'woocommerce_variation_id'], 'idx_woocommerce_variation');
                    }
                    
                    if (!$this->indexExists('variations', 'idx_woocommerce_variation_id')) {
                        $table->index(['woocommerce_variation_id'], 'idx_woocommerce_variation_id');
                    }
                });
            }
            
            // Variation templates table - optimize attribute sync
            if (Schema::hasTable('variation_templates')) {
                Schema::table('variation_templates', function (Blueprint $table) {
                    if (!$this->indexExists('variation_templates', 'idx_woocommerce_attr')) {
                        $table->index(['business_id', 'woocommerce_attr_id'], 'idx_woocommerce_attr');
                    }
                });
            }
            
            // Tax rates table - optimize tax mapping
            if (Schema::hasTable('tax_rates')) {
                Schema::table('tax_rates', function (Blueprint $table) {
                    if (!$this->indexExists('tax_rates', 'idx_woocommerce_tax_rate')) {
                        $table->index(['business_id', 'woocommerce_tax_rate_id'], 'idx_woocommerce_tax_rate');
                    }
                });
            }
            
            // Transactions table - optimize order sync
            if (Schema::hasTable('transactions')) {
                Schema::table('transactions', function (Blueprint $table) {
                    if (!$this->indexExists('transactions', 'idx_woocommerce_order')) {
                        $table->index(['business_id', 'woocommerce_order_id'], 'idx_woocommerce_order');
                    }
                    
                    if (!$this->indexExists('transactions', 'idx_woocommerce_order_id')) {
                        $table->index(['woocommerce_order_id'], 'idx_woocommerce_order_id');
                    }
                });
            }
            
            // Media table - optimize image sync
            if (Schema::hasTable('media')) {
                Schema::table('media', function (Blueprint $table) {
                    if (!$this->indexExists('media', 'idx_woocommerce_media')) {
                        $table->index(['business_id', 'woocommerce_media_id'], 'idx_woocommerce_media');
                    }
                });
            }
            
            // WooCommerce sync logs - optimize log queries
            if (Schema::hasTable('woocommerce_sync_logs')) {
                Schema::table('woocommerce_sync_logs', function (Blueprint $table) {
                    if (!$this->indexExists('woocommerce_sync_logs', 'idx_sync_type_business')) {
                        $table->index(['business_id', 'sync_type', 'created_at'], 'idx_sync_type_business');
                    }
                    
                    if (!$this->indexExists('woocommerce_sync_logs', 'idx_operation_type')) {
                        $table->index(['business_id', 'operation_type'], 'idx_operation_type');
                    }
                });
            }
            
        } catch (\Exception $e) {
            // Log error but don't fail migration
            \Log::error('WooCommerce performance indexes migration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            // Drop WooCommerce performance indexes
            
            if (Schema::hasTable('products')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropIndex('idx_woocommerce_sync');
                    $table->dropIndex('idx_woocommerce_product_id');
                    $table->dropIndex('idx_updated_at_sync');
                });
            }
            
            if (Schema::hasTable('categories')) {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropIndex('idx_woocommerce_cat');
                    $table->dropIndex('idx_woocommerce_cat_id');
                    $table->dropIndex('idx_category_sync');
                });
            }
            
            if (Schema::hasTable('variations')) {
                Schema::table('variations', function (Blueprint $table) {
                    $table->dropIndex('idx_woocommerce_variation');
                    $table->dropIndex('idx_woocommerce_variation_id');
                });
            }
            
            if (Schema::hasTable('variation_templates')) {
                Schema::table('variation_templates', function (Blueprint $table) {
                    $table->dropIndex('idx_woocommerce_attr');
                });
            }
            
            if (Schema::hasTable('tax_rates')) {
                Schema::table('tax_rates', function (Blueprint $table) {
                    $table->dropIndex('idx_woocommerce_tax_rate');
                });
            }
            
            if (Schema::hasTable('transactions')) {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->dropIndex('idx_woocommerce_order');
                    $table->dropIndex('idx_woocommerce_order_id');
                });
            }
            
            if (Schema::hasTable('media')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->dropIndex('idx_woocommerce_media');
                });
            }
            
            if (Schema::hasTable('woocommerce_sync_logs')) {
                Schema::table('woocommerce_sync_logs', function (Blueprint $table) {
                    $table->dropIndex('idx_sync_type_business');
                    $table->dropIndex('idx_operation_type');
                });
            }
            
        } catch (\Exception $e) {
            // Log error but don't fail rollback
            \Log::error('WooCommerce performance indexes rollback error', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
}