<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Business Information API Controller
 * 
 * SECURITY NOTICE: All business operations are READ-ONLY via API for security reasons.
 * Business settings, configuration changes, and sensitive business data modifications
 * must be performed through the web interface only.
 */
class BusinessController extends BaseApiController
{
    /**
     * Display business information
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(): JsonResponse
    {
        try {
            $business = $this->getBusiness();

            return $this->successResponse([
                'id' => $business->id,
                'name' => $business->name,
                'currency_id' => $business->currency_id,
                'start_date' => $business->start_date,
                'tax_number_1' => $business->tax_number_1,
                'tax_label_1' => $business->tax_label_1,
                'tax_number_2' => $business->tax_number_2,
                'tax_label_2' => $business->tax_label_2,
                'code_label_1' => $business->code_label_1,
                'code_label_2' => $business->code_label_2,
                'default_sales_tax' => $business->default_sales_tax,
                'default_profit_percent' => (float) $business->default_profit_percent,
                'owner' => [
                    'id' => $business->owner_id,
                    'name' => $business->owner->first_name . ' ' . $business->owner->last_name,
                    'email' => $business->owner->email,
                ],
                'created_at' => $business->created_at->toISOString(),
                'updated_at' => $business->updated_at->toISOString(),
            ], 'Business information retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve business information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get business locations
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function locations(): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $locations = \App\BusinessLocation::where('business_id', $business_id)
                ->get(['id', 'name', 'landmark', 'country', 'state', 'city', 'zip_code', 'is_active']);

            return $this->successResponse(
                $locations->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'landmark' => $location->landmark,
                        'country' => $location->country,
                        'state' => $location->state,
                        'city' => $location->city,
                        'zip_code' => $location->zip_code,
                        'is_active' => (bool) $location->is_active,
                    ];
                }),
                'Business locations retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve locations: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get business settings (READ-ONLY)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function settings(): JsonResponse
    {
        try {
            $business = $this->getBusiness();
            
            // Return safe, read-only business settings
            $settings = [
                'business_name' => $business->name,
                'currency' => [
                    'id' => $business->currency_id,
                    'code' => $business->currency ? $business->currency->code : null,
                    'symbol' => $business->currency ? $business->currency->symbol : null,
                ],
                'fiscal_year' => [
                    'start_date' => $business->fy_start_month,
                    'accounting_method' => $business->accounting_method,
                ],
                'tax_settings' => [
                    'tax_number_1' => $business->tax_number_1,
                    'tax_label_1' => $business->tax_label_1,
                    'tax_number_2' => $business->tax_number_2,
                    'tax_label_2' => $business->tax_label_2,
                    'default_sales_tax' => (float) $business->default_sales_tax,
                ],
                'business_info' => [
                    'start_date' => $business->start_date,
                    'default_profit_percent' => (float) $business->default_profit_percent,
                    'time_zone' => $business->time_zone,
                ],
                'display_settings' => [
                    'currency_symbol_placement' => $business->currency_symbol_placement,
                    'thousand_separator' => $business->thousand_separator,
                    'decimal_separator' => $business->decimal_separator,
                    'currency_precision' => (int) $business->currency_precision,
                ],
                'readonly_notice' => 'These settings are read-only via API for security reasons. Use the web interface to modify business settings.',
            ];

            return $this->successResponse($settings, 'Business settings retrieved successfully (read-only)');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve business settings: ' . $e->getMessage(), 500);
        }
    }
}