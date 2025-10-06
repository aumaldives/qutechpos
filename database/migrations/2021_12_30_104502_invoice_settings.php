<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice-settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('template_name');
            $table->string('template_color');
            $table->timestamps();
        });

        $input = \App\Models\Setting::INVOICE__TEMPLATE_ARRAY;
        foreach ($input as $key => $value) {

            DB::table('invoice-settings')->insert([
                'key' => $key,
                'template_name' => $value,
                'template_color' => '#000000',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice-settings');
    }
};
