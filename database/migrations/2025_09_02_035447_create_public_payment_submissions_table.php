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
        Schema::create('public_payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('contact_id');
            $table->decimal('total_amount', 22, 4);
            $table->string('payment_method', 50)->default('bank_transfer');
            $table->text('receipt_file_path')->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_mobile')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'processed', 'rejected'])->default('pending');
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('processed_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->foreign('business_id')->references('id')->on('business');
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('processed_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('public_payment_submissions');
    }
};
