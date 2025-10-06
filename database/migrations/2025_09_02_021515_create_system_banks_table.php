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
        Schema::create('system_banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g., 'BML', 'MIB'
            $table->string('full_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('country', 3)->default('MV'); // ISO country code
            $table->boolean('is_active')->default(true);
            $table->json('additional_info')->nullable(); // Swift code, website, etc.
            $table->timestamps();
            
            $table->index(['country', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_banks');
    }
};
