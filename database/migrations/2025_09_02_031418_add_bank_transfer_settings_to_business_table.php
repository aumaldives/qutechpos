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
        Schema::table('business', function (Blueprint $table) {
            $table->boolean('enable_bank_transfer_payment')->default(false)->after('id');
            $table->boolean('auto_approve_bank_payments')->default(false)->after('enable_bank_transfer_payment');
            $table->boolean('send_bank_payment_notifications')->default(false)->after('auto_approve_bank_payments');
            $table->decimal('max_bank_transfer_amount', 20, 4)->nullable()->after('send_bank_payment_notifications');
            $table->decimal('min_bank_transfer_amount', 20, 4)->default(0.01)->after('max_bank_transfer_amount');
            $table->text('bank_transfer_instructions')->nullable()->after('min_bank_transfer_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn([
                'enable_bank_transfer_payment',
                'auto_approve_bank_payments', 
                'send_bank_payment_notifications',
                'max_bank_transfer_amount',
                'min_bank_transfer_amount',
                'bank_transfer_instructions'
            ]);
        });
    }
};
