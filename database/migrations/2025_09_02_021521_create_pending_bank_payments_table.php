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
        Schema::create('pending_bank_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('transaction_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->decimal('amount', 22, 4);
            $table->string('receipt_file_path');
            $table->text('notes')->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->timestamp('submitted_at');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('processed_by')->nullable(); // User ID who processed
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('payment_id')->nullable(); // Transaction payment ID when approved
            $table->timestamps();
            
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('business_bank_accounts')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('payment_id')->references('id')->on('transaction_payments')->onDelete('set null');
            
            $table->index(['business_id', 'status']);
            $table->index(['transaction_id', 'status']);
            $table->index(['submitted_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pending_bank_payments');
    }
};
