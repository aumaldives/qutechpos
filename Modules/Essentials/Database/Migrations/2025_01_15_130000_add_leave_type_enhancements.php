<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveTypeEnhancements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('essentials_leave_types', function (Blueprint $table) {
            $table->boolean('is_paid')->default(1)->after('max_leave_count');
        });
        
        // Add 'join_date_anniversary' option to existing enum by updating records manually
        // The enum change will be handled through application logic rather than schema change
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('essentials_leave_types', function (Blueprint $table) {
            $table->dropColumn('is_paid');
        });
    }
}