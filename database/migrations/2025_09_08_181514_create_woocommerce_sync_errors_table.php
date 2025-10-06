<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_sync_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->string('sync_job_id')->nullable();
            $table->enum('sync_type', ['products', 'orders', 'customers', 'inventory', 'all']);
            $table->enum('error_category', [
                'api_authentication',
                'api_rate_limit', 
                'api_connection',
                'data_validation',
                'entity_not_found',
                'business_logic',
                'system_error',
                'configuration'
            ]);
            $table->string('error_code')->nullable();
            $table->text('error_message');
            $table->json('error_context')->nullable();
            $table->enum('affected_entity_type', ['product', 'order', 'customer', 'inventory', 'category'])->nullable();
            $table->string('affected_entity_id')->nullable();
            $table->unsignedInteger('recovery_attempts')->default(0);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_method')->nullable();
            $table->enum('severity_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->timestamp('retry_after')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['business_id', 'location_id']);
            $table->index(['error_category', 'severity_level']);
            $table->index(['is_resolved', 'retry_after']);
            $table->index(['sync_type', 'created_at']);
            
            // Foreign key constraints
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_sync_errors');
    }
};
