<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQuickbooksFieldsToContacts extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('quickbooks_customer_id')->nullable()->index();
            $table->string('quickbooks_vendor_id')->nullable()->index();
            $table->string('quickbooks_sync_token')->nullable();
            $table->timestamp('quickbooks_last_synced_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'quickbooks_customer_id',
                'quickbooks_vendor_id', 
                'quickbooks_sync_token',
                'quickbooks_last_synced_at'
            ]);
        });
    }
}