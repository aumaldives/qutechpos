<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWoocommerceSyncQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->enum('sync_type', ['categories', 'products', 'orders', 'customers', 'stock', 'attributes'])
                  ->comment('Type of entity being synced');
            $table->string('entity_type', 50)
                  ->comment('Specific entity type (e.g., product, variation, category)');
            $table->unsignedInteger('entity_id')
                  ->comment('ID of the POS entity');
            $table->enum('operation', ['create', 'update', 'delete'])
                  ->comment('Sync operation to perform');
            $table->json('payload')
                  ->comment('Data payload for the sync operation');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                  ->default('pending')
                  ->comment('Current status of the sync item');
            $table->unsignedTinyInteger('attempts')->default(0)
                  ->comment('Number of processing attempts');
            $table->unsignedTinyInteger('max_attempts')->default(3)
                  ->comment('Maximum number of retry attempts');
            $table->integer('priority')->default(0)
                  ->comment('Processing priority (higher number = higher priority)');
            $table->timestamp('scheduled_at')->useCurrent()
                  ->comment('When the item should be processed');
            $table->timestamp('started_at')->nullable()
                  ->comment('When processing started');
            $table->timestamp('processed_at')->nullable()
                  ->comment('When processing completed');
            $table->text('error_message')->nullable()
                  ->comment('Error message if processing failed');
            $table->json('error_context')->nullable()
                  ->comment('Additional error context and debugging info');
            $table->string('batch_id', 36)->nullable()
                  ->comment('UUID for grouping related sync operations');
            $table->unsignedInteger('woocommerce_id')->nullable()
                  ->comment('WooCommerce entity ID after successful sync');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            
            // Performance indexes
            $table->index(['business_id', 'status'], 'idx_business_status');
            $table->index(['status', 'scheduled_at'], 'idx_scheduled');
            $table->index(['business_id', 'entity_type', 'entity_id'], 'idx_entity');
            $table->index(['batch_id'], 'idx_batch');
            $table->index(['sync_type', 'status'], 'idx_type_status');
            $table->index(['priority', 'scheduled_at'], 'idx_priority_scheduled');
            $table->index(['created_at'], 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_sync_queue');
    }
}