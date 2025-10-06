<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateWoocommerceSyncLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woocommerce_sync_logs', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('woocommerce_sync_logs', 'location_id')) {
                $table->unsignedInteger('location_id')->after('business_id')->nullable();
            }
            
            if (!Schema::hasColumn('woocommerce_sync_logs', 'status')) {
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                      ->default('completed')
                      ->after('sync_type');
            }
            
            if (!Schema::hasColumn('woocommerce_sync_logs', 'message')) {
                $table->text('message')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('woocommerce_sync_logs', 'records_processed')) {
                $table->integer('records_processed')->default(0)->after('details');
            }
            
            if (!Schema::hasColumn('woocommerce_sync_logs', 'records_success')) {
                $table->integer('records_success')->default(0)->after('records_processed');
            }
            
            if (!Schema::hasColumn('woocommerce_sync_logs', 'records_failed')) {
                $table->integer('records_failed')->default(0)->after('records_success');
            }
            
            if (!Schema::hasColumn('woocommerce_sync_logs', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('records_failed');
            }
            
            if (!Schema::hasColumn('woocommerce_sync_logs', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
            
            if (!Schema::hasColumn('woocommerce_sync_logs', 'duration_seconds')) {
                $table->integer('duration_seconds')->nullable()->after('completed_at');
            }
        });

        // Add foreign key constraints and indexes
        Schema::table('woocommerce_sync_logs', function (Blueprint $table) {
            // Add foreign key constraints if they don't exist
            if (Schema::hasColumn('woocommerce_sync_logs', 'location_id')) {
                $table->foreign('location_id')
                      ->references('id')
                      ->on('business_locations')
                      ->onDelete('cascade');
            }

            // Add indexes for performance
            $table->index(['business_id', 'location_id'], 'idx_business_location');
            $table->index(['business_id', 'sync_type'], 'idx_business_sync_type');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index('created_at', 'idx_created_at');
        });

        // Update existing records to have a default status
        DB::statement("UPDATE woocommerce_sync_logs SET status = 'completed' WHERE status IS NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woocommerce_sync_logs', function (Blueprint $table) {
            // Remove foreign key constraints
            $table->dropForeign(['location_id']);
            
            // Remove indexes
            $table->dropIndex('idx_business_location');
            $table->dropIndex('idx_business_sync_type');
            $table->dropIndex('idx_status_created');
            $table->dropIndex('idx_created_at');
            
            // Remove added columns
            $table->dropColumn([
                'location_id',
                'status',
                'message', 
                'records_processed',
                'records_success',
                'records_failed',
                'started_at',
                'completed_at',
                'duration_seconds'
            ]);
        });
    }
}