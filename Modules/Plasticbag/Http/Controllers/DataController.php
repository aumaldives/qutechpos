<?php

namespace Modules\Plasticbag\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    public function superadmin_package()
    {
        return [
            [
                'name' => 'plasticbag_module',
                'label' => __('plasticbag::lang.plasticbag_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Adds Connectoe menus
     *
     * @return null
     */
    public function modifyAdminMenu()
    {
        $module_util = new ModuleUtil();

        if (auth()->user()->can('superadmin')) {
            $is_plasticbag_enabled = $module_util->isModuleInstalled('Plasticbag');
        } else {
            $business_id = session()->get('user.business_id');
            $is_plasticbag_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'plasticbag_module', 'superadmin_package');
        }
        // Plasticbag menu removed
        // if ($is_plasticbag_enabled) {
        //     Menu::modify('admin-sidebar-menu', function ($menu) {
        //         $menu->url(
        //             action([\Modules\Plasticbag\Http\Controllers\SettingsController::class, 'index']),
        //             __('plasticbag::lang.plasticbag'),
        //             ['icon' => 'fa fas fa-network-wired', 'active' => request()->segment(1) == 'plasticbag' && request()->segment(2) == 'api']
        //         )->order(89);
        //     });
        // }
    }
}
