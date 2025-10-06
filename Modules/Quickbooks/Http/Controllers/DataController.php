<?php

namespace Modules\Quickbooks\Http\Controllers;

use Illuminate\Routing\Controller;

class DataController extends Controller
{
    /**
     * Defines package permissions for the module.
     * This method is called by the superadmin package system.
     *
     * @return array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'quickbooks_module',
                'label' => __('quickbooks::lang.quickbooks_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Defines user permissions for the module.
     * This method defines what permissions are available for QuickBooks functionality.
     *
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'quickbooks.sync_customers',
                'label' => __('quickbooks::lang.sync_customers'),
                'default' => false,
            ],
            [
                'value' => 'quickbooks.sync_suppliers',
                'label' => __('quickbooks::lang.sync_suppliers'),
                'default' => false,
            ],
            [
                'value' => 'quickbooks.sync_products',
                'label' => __('quickbooks::lang.sync_products'),
                'default' => false,
            ],
            [
                'value' => 'quickbooks.sync_invoices',
                'label' => __('quickbooks::lang.sync_invoices'),
                'default' => false,
            ],
            [
                'value' => 'quickbooks.sync_payments',
                'label' => __('quickbooks::lang.sync_payments'),
                'default' => false,
            ],
            [
                'value' => 'quickbooks.sync_purchases',
                'label' => __('quickbooks::lang.sync_purchases'),
                'default' => false,
            ],
            [
                'value' => 'quickbooks.access_quickbooks_api_settings',
                'label' => __('quickbooks::lang.access_quickbooks_api_settings'),
                'default' => false,
            ],
            [
                'value' => 'quickbooks.manage_connections',
                'label' => __('quickbooks::lang.manage_connections'),
                'default' => false,
            ],
        ];
    }

    /**
     * Add menu items for the module.
     * This method is called to add QuickBooks menu items to the admin sidebar.
     *
     * @return void
     */
    public function modifyAdminMenu()
    {
        return null; // QuickBooks is accessed via integrations page
    }
}