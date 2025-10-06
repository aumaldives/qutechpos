<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modify the status enum to include 'draft'
        DB::statement("ALTER TABLE essentials_overtime_requests MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // First, convert any draft records to pending to avoid constraint violation
        DB::table('essentials_overtime_requests')
            ->where('status', 'draft')
            ->update(['status' => 'pending']);
            
        // Then revert to original enum values
        DB::statement("ALTER TABLE essentials_overtime_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
