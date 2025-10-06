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
        Schema::create('essentials_overtime_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->date('overtime_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours_requested', 8, 2);
            $table->enum('overtime_type', ['workday', 'weekend', 'holiday'])->default('workday');
            $table->text('reason')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('approved_by')->unsigned()->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('approved_hours', 8, 2)->nullable();
            $table->decimal('multiplier_rate', 8, 2)->nullable();
            $table->decimal('hourly_rate', 10, 4)->nullable();
            $table->decimal('total_amount', 10, 4)->nullable();
            $table->boolean('is_processed_in_payroll')->default(0);
            $table->integer('payroll_transaction_id')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('payroll_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            
            $table->index(['business_id', 'user_id', 'overtime_date'], 'overtime_requests_lookup_index');
            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_overtime_requests');
    }
};
