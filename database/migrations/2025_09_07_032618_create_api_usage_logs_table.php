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
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('api_key_id')->unsigned();
            $table->integer('business_id')->unsigned();
            $table->string('endpoint', 255)->comment('API endpoint path');
            $table->string('method', 10)->comment('HTTP method (GET, POST, etc.)');
            $table->string('ip_address', 45)->comment('Client IP address (supports IPv6)');
            $table->string('user_agent', 500)->nullable()->comment('Client user agent string');
            $table->integer('response_status')->comment('HTTP response status code');
            $table->integer('response_time_ms')->comment('Response time in milliseconds');
            $table->json('request_data')->nullable()->comment('Request parameters/body (truncated)');
            $table->json('response_data')->nullable()->comment('Response data sample (truncated)');
            $table->text('error_message')->nullable()->comment('Error message if request failed');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('api_key_id')->references('id')->on('api_keys')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            
            // Indexes for performance and analytics
            $table->index(['api_key_id', 'created_at'], 'idx_key_timestamp');
            $table->index(['business_id', 'created_at'], 'idx_business_timestamp');
            $table->index(['endpoint', 'method'], 'idx_endpoint_method');
            $table->index(['response_status'], 'idx_response_status');
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
        Schema::dropIfExists('api_usage_logs');
    }
};
