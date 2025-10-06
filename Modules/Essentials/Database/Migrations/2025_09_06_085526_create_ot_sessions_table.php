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
        Schema::create('ot_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('business_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->text('start_note')->nullable();
            $table->text('end_note')->nullable();
            $table->string('status')->default('active'); // active, completed
            $table->timestamps();

            $table->index(['user_id', 'business_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ot_sessions');
    }
};
