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
        Schema::table('public_payment_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('payment_method');
            $table->foreign('bank_account_id')->references('id')->on('business_bank_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('public_payment_submissions', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn('bank_account_id');
        });
    }
};
