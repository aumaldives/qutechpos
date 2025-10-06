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
        // For MySQL, we need to use raw SQL to modify the enum
        DB::statement("ALTER TABLE cash_register_transactions MODIFY COLUMN transaction_type ENUM('initial', 'sell', 'transfer', 'refund', 'adjustment')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove 'adjustment' from the enum
        DB::statement("ALTER TABLE cash_register_transactions MODIFY COLUMN transaction_type ENUM('initial', 'sell', 'transfer', 'refund')");
    }
};
