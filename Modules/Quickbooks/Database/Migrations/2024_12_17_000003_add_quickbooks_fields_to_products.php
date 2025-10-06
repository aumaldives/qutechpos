<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQuickbooksFieldsToProducts extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('quickbooks_item_id')->nullable()->index();
            $table->string('quickbooks_sync_token')->nullable();
            $table->timestamp('quickbooks_last_synced_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'quickbooks_item_id',
                'quickbooks_sync_token',
                'quickbooks_last_synced_at'
            ]);
        });
    }
}