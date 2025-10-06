<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWoocommerceSyncExecutionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_sync_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->string('sync_type', 50);
            $table->unsignedBigInteger('sync_progress_id')->nullable();
            
            // Execution status and priority
            $table->enum('status', ['queued', 'dispatched', 'processing', 'completed', 'failed', 'cancelled', 'retry_pending'])->default('queued');
            $table->integer('priority')->default(5);
            
            // Timing information
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Result tracking
            $table->integer('records_processed')->default(0);
            $table->integer('records_success')->default(0);
            $table->integer('records_failed')->default(0);
            
            // Error handling
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('schedule_id')->references('id')->on('woocommerce_sync_schedules')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('sync_progress_id')->references('id')->on('woocommerce_sync_progress')->onDelete('set null');

            // Indexes for performance
            $table->index(['schedule_id', 'created_at']);
            $table->index(['business_id', 'location_id']);
            $table->index(['business_id', 'status']);
            $table->index(['status', 'next_retry_at']); // For retry processing
            $table->index(['status', 'started_at']);
            $table->index('priority');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_sync_executions');
    }
}