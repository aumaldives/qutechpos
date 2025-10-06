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
        if (!Schema::hasTable('telegram_bot_settings')) {
            Schema::create('telegram_bot_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id');
                $table->string('bot_token')->nullable();
                $table->text('authorized_chat_ids')->nullable(); // JSON array of authorized chat IDs
                $table->boolean('is_active')->default(false);
                $table->json('settings')->nullable(); // Additional bot settings
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->unique('business_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telegram_bot_settings');
    }
};