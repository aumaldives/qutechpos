<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddIntegrationPermissionsToAdminRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create the integration permissions if they don't exist
        $permissions = [
            'access_integrations',
            'manage_api_keys',
            'view_api_docs'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Grant all integration permissions to all Admin roles
        $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
        
        foreach ($adminRoles as $adminRole) {
            foreach ($permissions as $permission) {
                $adminRole->givePermissionTo($permission);
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
        // Remove integration permissions from all Admin roles
        $permissions = [
            'access_integrations',
            'manage_api_keys', 
            'view_api_docs'
        ];

        $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
        
        foreach ($adminRoles as $adminRole) {
            foreach ($permissions as $permission) {
                if ($adminRole->hasPermissionTo($permission)) {
                    $adminRole->revokePermissionTo($permission);
                }
            }
        }

        // Delete the permissions
        foreach ($permissions as $permission) {
            $permissionModel = Permission::where('name', $permission)->first();
            if ($permissionModel) {
                $permissionModel->delete();
            }
        }
    }
}