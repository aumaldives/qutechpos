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
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('is_per_location_pricing')->default(false)->after('price');
            $table->decimal('price_per_location', 8, 4)->nullable()->after('is_per_location_pricing');
            $table->integer('min_locations')->default(1)->after('price_per_location');
            $table->integer('max_locations')->default(0)->after('min_locations'); // 0 = unlimited
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['is_per_location_pricing', 'price_per_location', 'min_locations', 'max_locations']);
        });
    }
};
