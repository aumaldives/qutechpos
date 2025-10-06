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
        Schema::table('api_keys', function (Blueprint $table) {
            $table->enum('access_level', ['business', 'system', 'superadmin'])
                  ->default('business')
                  ->after('abilities')
                  ->comment('API access level: business=normal, system=internal, superadmin=full');
            
            $table->boolean('is_internal')
                  ->default(false)
                  ->after('access_level')
                  ->comment('Internal system API key flag');
            
            $table->integer('created_by_superadmin_id')
                  ->nullable()
                  ->after('is_internal')
                  ->comment('Superadmin user ID who created this key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn(['access_level', 'is_internal', 'created_by_superadmin_id']);
        });
    }
};