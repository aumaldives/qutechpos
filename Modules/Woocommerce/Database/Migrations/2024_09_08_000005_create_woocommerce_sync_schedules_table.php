<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWoocommerceSyncSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_sync_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            
            // Schedule identification
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sync_type', 50); // all, products, orders, customers, inventory
            
            // Scheduling configuration
            $table->string('cron_expression'); // e.g., '0 */6 * * *' for every 6 hours
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(5); // 1=low, 5=normal, 8=high, 10=critical
            $table->string('timezone', 50)->default('UTC');
            
            // Execution limits
            $table->integer('max_runtime_minutes')->default(60); // Max allowed runtime
            $table->integer('retry_attempts')->default(3);
            $table->integer('retry_delay_minutes')->default(15);
            
            // Conditions and notifications
            $table->json('conditions')->nullable(); // Execution conditions (time ranges, etc.)
            $table->json('notifications')->nullable(); // Notification settings
            
            // Execution tracking
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            
            // Audit information
            $table->unsignedInteger('created_by_user_id')->nullable();
            $table->json('metadata')->nullable(); // Additional schedule-specific data
            
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['business_id', 'is_active'], 'woo_sync_sched_biz_active');
            $table->index(['business_id', 'location_id', 'is_active'], 'woo_sync_sched_biz_loc_active');
            $table->index(['is_active', 'next_run_at'], 'woo_sync_sched_active_next'); // For finding due schedules
            $table->index('priority', 'woo_sync_sched_priority');
            $table->index('sync_type', 'woo_sync_sched_type');
            $table->index('next_run_at', 'woo_sync_sched_next_run');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_sync_schedules');
    }
}