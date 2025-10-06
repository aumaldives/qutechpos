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
        Schema::table('transactions', function (Blueprint $table) {
            // Add composite index matching our exact query pattern for optimal performance
            // Order: business_id, type, status, transaction_date (most selective to least)
            $table->index(['business_id', 'type', 'status', 'transaction_date'], 'idx_sells_date_filter');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the composite index
            $table->dropIndex('idx_sells_date_filter');
        });
    }
};
