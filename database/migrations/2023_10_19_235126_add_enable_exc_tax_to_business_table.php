<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnableExcTaxColumnToBusinessTable extends Migration
{
    public function up()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->tinyInteger('enable_exc_tax')->default(1)->after('enable_inline_tax');
        });
    }

    public function down()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn('enable_exc_tax');
        });
    }
}
