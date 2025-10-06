<?php

namespace Modules\AgeingReport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Utils\ModuleUtil;
use Menu;

class DataController extends Controller
{    
    public function superadmin_package()
    {
        return [
            [
                'name' => 'ageingreport_module',
                'label' => __('Ageing Balance Report'),
                'default' => false
            ]
        ];
    }

    /**
      * Defines user permissions for the module.
      * @return array
      */
    public function user_permissions()
    {
        return [
            [
                'value' => 'ageingreport.view',
                'label' => __('ageingreport::app.view'),
                'default' => false
            ],
      
        ];
    }

    /**
    * Function to add module taxonomies
    * @return array
    */
    
    public function addTaxonomies()
    {
        $business_id = request()->session()->get('user.business_id');

        $module_util = new ModuleUtil();
        if (!(auth()->user()->can('superadmin') || $module_util->hasThePermissionInSubscription($business_id, 'ageingreport_module'))) {
            return ['wallet' => []];
        }
        
        return [
            'wallet' => [
                  ]
        ];
    }

    /**
     * Adds Report menus
     * @return null
     */

 public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();
        $is_ageingreport_enabled = (boolean)$module_util->hasThePermissionInSubscription($business_id, 'ageingreport_module');
        $background_color = '';
        if (config('app.env') == 'demo') {$background_color = '#4A90E2  !important';}
        if ($is_ageingreport_enabled && (auth()->user()->can('superadmin') || auth()->user()->can('ageingreport.view'))) {
            /* $menuparent = Menu::instance('admin-sidebar-menu');
            $menuparent->url (
                action('\Modules\AgeingReport\Http\Controllers\AgeingReportController@index'),
                __('ageingreport::app.ageingreport'), 
            ['icon' => 'fa fa-clock', 'style' => "background-color:$background_color"]
            )->order(54); */
        }
    }

}