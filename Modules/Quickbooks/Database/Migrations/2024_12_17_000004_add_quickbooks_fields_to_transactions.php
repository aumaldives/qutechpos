<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQuickbooksFieldsToTransactions extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('quickbooks_invoice_id')->nullable()->index();
            $table->string('quickbooks_bill_id')->nullable()->index();
            $table->string('quickbooks_sync_token')->nullable();
            $table->timestamp('quickbooks_last_synced_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'quickbooks_invoice_id',
                'quickbooks_bill_id',
                'quickbooks_sync_token',
                'quickbooks_last_synced_at'
            ]);
        });
    }
}