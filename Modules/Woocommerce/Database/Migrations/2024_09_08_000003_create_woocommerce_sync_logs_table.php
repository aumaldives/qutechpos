<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWoocommerceSyncLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->string('sync_type', 50); // products, orders, customers, inventory, etc.
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('message')->nullable();
            $table->json('details')->nullable(); // Additional details like counts, errors, etc.
            $table->integer('records_processed')->default(0);
            $table->integer('records_success')->default(0);
            $table->integer('records_failed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable(); // Calculated field
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');

            // Indexes for performance
            $table->index(['business_id', 'location_id']);
            $table->index(['business_id', 'sync_type']);
            $table->index(['status', 'created_at']);
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
        Schema::dropIfExists('woocommerce_sync_logs');
    }
}