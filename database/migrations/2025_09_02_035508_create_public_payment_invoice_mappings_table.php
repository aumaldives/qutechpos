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
        Schema::create('public_payment_invoice_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_submission_id');
            $table->unsignedInteger('transaction_id');
            $table->decimal('applied_amount', 22, 4);
            $table->timestamps();
            
            $table->foreign('payment_submission_id')->references('id')->on('public_payment_submissions');
            $table->foreign('transaction_id')->references('id')->on('transactions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('public_payment_invoice_mappings');
    }
};
