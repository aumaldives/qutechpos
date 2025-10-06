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
        Schema::create('kitchen_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('setting_key');
            $table->text('setting_value')->nullable();
            $table->timestamps();
            
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'setting_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kitchen_settings');
    }
};
