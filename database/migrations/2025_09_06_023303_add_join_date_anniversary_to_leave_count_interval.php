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
        // Check if the column exists and needs updating
        $columnInfo = DB::select("SHOW COLUMNS FROM essentials_leave_types WHERE Field = 'leave_count_interval'");
        
        if (!empty($columnInfo)) {
            $columnType = $columnInfo[0]->Type;
            
            // Check if 'join_date_anniversary' is already in the enum
            if (strpos($columnType, 'join_date_anniversary') === false) {
                // Update the enum to include 'join_date_anniversary'
                DB::statement("ALTER TABLE essentials_leave_types MODIFY COLUMN leave_count_interval ENUM('month', 'year', 'join_date_anniversary') NULL");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Only revert if no records are using 'join_date_anniversary'
        $usingJoinDate = DB::table('essentials_leave_types')
            ->where('leave_count_interval', 'join_date_anniversary')
            ->exists();
            
        if (!$usingJoinDate) {
            DB::statement("ALTER TABLE essentials_leave_types MODIFY COLUMN leave_count_interval ENUM('month', 'year') NULL");
        }
    }
};
