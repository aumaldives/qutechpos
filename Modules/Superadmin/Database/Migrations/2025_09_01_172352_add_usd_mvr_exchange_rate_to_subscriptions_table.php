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
            $table->decimal('usd_to_mvr_rate', 8, 4)->nullable()->after('package_price')->comment('USD to MVR exchange rate at time of subscription');
            $table->decimal('mvr_amount', 10, 2)->nullable()->after('usd_to_mvr_rate')->comment('Package price converted to MVR');
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
            $table->dropColumn(['usd_to_mvr_rate', 'mvr_amount']);
        });
    }
};
