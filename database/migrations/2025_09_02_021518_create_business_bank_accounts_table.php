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
        Schema::create('business_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable(); // null means all locations
            $table->unsignedBigInteger('bank_id'); // Foreign key to system_banks
            $table->string('account_name');
            $table->string('account_number');
            $table->string('account_type')->default('Current'); // Current, Savings, etc.
            $table->string('swift_code')->nullable();
            $table->string('branch_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('bank_id')->references('id')->on('system_banks')->onDelete('cascade');
            
            $table->index(['business_id', 'is_active']);
            $table->index(['business_id', 'location_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_bank_accounts');
    }
};
