<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWoocommerceSyncProgressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_sync_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->string('sync_type', 50); // all, products, orders, customers, inventory
            $table->enum('status', ['pending', 'processing', 'paused', 'completed', 'failed', 'cancelled'])->default('pending');
            
            // Progress tracking
            $table->decimal('progress_percentage', 5, 2)->default(0); // 0.00 to 100.00
            $table->integer('current_step')->default(0);
            $table->integer('total_steps')->default(0);
            
            // Record counting
            $table->integer('records_processed')->default(0);
            $table->integer('records_total')->default(0);
            $table->integer('records_success')->default(0);
            $table->integer('records_failed')->default(0);
            
            // Status information
            $table->string('current_operation', 255)->nullable();
            $table->integer('estimated_time_remaining')->nullable(); // seconds
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Error handling
            $table->text('error_message')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable(); // For storing additional sync-specific data
            
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');

            // Indexes for performance
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'location_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('started_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_sync_progress');
    }
}