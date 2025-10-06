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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('name')->comment('Human-readable name for the API key');
            $table->string('key_prefix', 8)->unique()->comment('Short prefix for key identification (e.g., ib_abc4)');
            $table->string('key_hash')->comment('SHA-256 hash of the full API key');
            $table->string('last_4', 4)->comment('Last 4 characters of key for display');
            $table->json('abilities')->nullable()->comment('API permissions/scopes');
            $table->integer('rate_limit_per_minute')->default(60)->comment('Rate limiting per minute');
            $table->timestamp('last_used_at')->nullable()->comment('Last API usage timestamp');
            $table->timestamp('expires_at')->nullable()->comment('Optional expiration date');
            $table->boolean('is_active')->default(true)->comment('Whether the key is active');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['business_id']);
            $table->index(['key_hash']);
            $table->index(['business_id', 'is_active', 'expires_at'], 'idx_active_keys');
            $table->index(['last_used_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_keys');
    }
};
