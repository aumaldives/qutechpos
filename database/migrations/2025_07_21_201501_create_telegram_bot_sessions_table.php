<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelegramBotSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_bot_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('chat_id');
            $table->string('current_action')->nullable(); // 'add_expense', 'view_reports', etc.
            $table->json('session_data')->nullable(); // Store temporary data during conversation
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index(['business_id', 'chat_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telegram_bot_sessions');
    }
}