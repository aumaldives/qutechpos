<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\System;

class AddMiraTaxSettingsToSystemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add MIRA tax settings to system table
        $settings = [
            'superadmin_business_tin' => null,
            'superadmin_tax_activity_number' => null,
            'superadmin_gst_percentage' => '8.00'
        ];

        foreach ($settings as $key => $value) {
            System::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $keys = ['superadmin_business_tin', 'superadmin_tax_activity_number', 'superadmin_gst_percentage'];
        
        foreach ($keys as $key) {
            System::where('key', $key)->delete();
        }
    }
}