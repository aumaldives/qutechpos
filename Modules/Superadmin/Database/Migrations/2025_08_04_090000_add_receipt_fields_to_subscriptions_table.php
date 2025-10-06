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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('receipt_file_path')->nullable()->after('payment_transaction_id');
            $table->string('selected_bank')->nullable()->after('receipt_file_path');
            $table->string('selected_currency')->nullable()->after('selected_bank');
            
            // Add index for faster queries
            $table->index('selected_bank');
            $table->index('selected_currency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['selected_bank']);
            $table->dropIndex(['selected_currency']);
            $table->dropColumn([
                'receipt_file_path',
                'selected_bank',
                'selected_currency'
            ]);
        });
    }
};