<?php

use Illuminate\Database\Migrations\Migration;

class AddPlasticbagModuleVersionToSystemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('system')->insert([
            'key' => 'plasticbag_version',
            'value' => config('plasticbag.module_version', 1.0),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
