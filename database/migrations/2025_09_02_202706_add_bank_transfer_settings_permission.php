<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddBankTransferSettingsPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create the new permission
        $permission = Permission::create([
            'name' => 'access_bank_transfer_settings',
            'guard_name' => 'web'
        ]);
        
        // Get all admin roles (those with name pattern 'Admin#business_id')
        $admin_roles = Role::where('name', 'like', 'Admin#%')->get();
        
        // Assign the permission to all admin roles
        foreach ($admin_roles as $role) {
            $role->givePermissionTo($permission);
        }
        
        // Also ensure admin roles have the existing pending payment permissions
        $pending_payment_permissions = [
            'access_pending_payments',
            'approve_pending_payments'
        ];
        
        foreach ($admin_roles as $role) {
            foreach ($pending_payment_permissions as $perm) {
                if (Permission::where('name', $perm)->exists()) {
                    $role->givePermissionTo($perm);
                }
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
        // Remove the permission from all roles
        $permission = Permission::where('name', 'access_bank_transfer_settings')->first();
        if ($permission) {
            $permission->delete();
        }
    }
}