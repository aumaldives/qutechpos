<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $now = \Carbon::now()->toDateTimeString();
        Permission::insert([
            [
                'name' => 'plastic_bag.access',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'plastic_bag.create',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'plastic_bag.update',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'plastic_bag.delete',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::whereIn('name', [
            'plastic_bag.access',
            'plastic_bag.create',
            'plastic_bag.update',
            'plastic_bag.delete'
        ])->delete();
    }
};