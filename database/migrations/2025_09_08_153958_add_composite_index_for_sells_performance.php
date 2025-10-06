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
            // Add composite index for performance optimization on sells page
            // This covers the common query pattern: business_id + type + transaction_date
            $table->index(['business_id', 'type', 'transaction_date'], 'idx_business_type_date');
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
            $table->dropIndex('idx_business_type_date');
        });
    }
};
