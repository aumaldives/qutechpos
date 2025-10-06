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
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webhook_id');
            $table->string('event_type', 50);
            $table->json('payload'); // The webhook payload data
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->decimal('response_time', 8, 3)->nullable(); // Response time in seconds
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->integer('attempt_number')->default(1);
            $table->timestamp('scheduled_at')->default(now());
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['webhook_id', 'status']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['event_type', 'created_at']);
            
            // Foreign key
            $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};