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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('name');
            $table->string('url', 500);
            $table->json('events'); // Array of subscribed events
            $table->string('secret', 100); // Webhook secret for signature verification
            $table->boolean('is_active')->default(true);
            $table->integer('timeout')->default(30); // Request timeout in seconds
            $table->integer('max_retries')->default(3);
            $table->integer('retry_delay')->default(60); // Initial retry delay in seconds
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('last_response_status')->nullable();
            $table->integer('failure_count')->default(0);
            $table->json('metadata')->nullable(); // Additional configuration
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['business_id', 'is_active']);
            $table->index('last_triggered_at');
            
            // Foreign key
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webhooks');
    }
};