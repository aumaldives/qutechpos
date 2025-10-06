<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataController extends Controller
{
    /**
     * Provides core user permissions that can be assigned to roles
     *
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'access_bank_transfer_settings',
                'label' => __('role.access_bank_transfer_settings'),
                'default' => false,
                'name' => 'access_bank_transfer_settings'
            ],
            [
                'value' => 'access_pending_payments',
                'label' => __('role.access_pending_payments'),
                'default' => false,
                'name' => 'access_pending_payments'
            ],
            [
                'value' => 'approve_pending_payments',
                'label' => __('role.approve_pending_payments'),
                'default' => false,
                'name' => 'approve_pending_payments'
            ],
            [
                'value' => 'access_integrations',
                'label' => __('role.access_integrations'),
                'default' => false,
                'name' => 'access_integrations'
            ],
            [
                'value' => 'manage_api_keys',
                'label' => __('role.manage_api_keys'),
                'default' => false,
                'name' => 'manage_api_keys'
            ],
            [
                'value' => 'view_api_docs',
                'label' => __('role.view_api_docs'),
                'default' => false,
                'name' => 'view_api_docs'
            ]
        ];
    }
}