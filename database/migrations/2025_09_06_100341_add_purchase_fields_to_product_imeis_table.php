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
        Schema::table('product_imeis', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_line_id')->nullable()->after('transaction_id');
            $table->unsignedInteger('location_id')->nullable()->after('purchase_line_id');
            $table->unsignedBigInteger('sell_line_id')->nullable()->after('location_id');
            $table->unsignedInteger('business_id')->nullable()->after('sell_line_id');
            
            // Add indexes for performance
            $table->index(['business_id', 'product_id']);
            $table->index(['location_id', 'is_sold']);
            $table->index('business_id');
            $table->index('imei');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_imeis', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'product_id']);
            $table->dropIndex(['location_id', 'is_sold']);
            $table->dropIndex(['business_id']);
            $table->dropIndex(['imei']);
            
            $table->dropColumn(['purchase_line_id', 'location_id', 'sell_line_id', 'business_id']);
        });
    }
};
