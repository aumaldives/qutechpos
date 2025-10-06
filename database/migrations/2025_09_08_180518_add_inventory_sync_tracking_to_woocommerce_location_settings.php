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
        Schema::table('woocommerce_location_settings', function (Blueprint $table) {
            $table->integer('total_inventory_synced')->default(0)->after('total_customers_synced');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woocommerce_location_settings', function (Blueprint $table) {
            $table->dropColumn('total_inventory_synced');
        });
    }
};
