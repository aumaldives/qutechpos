<?php

namespace Modules\Woocommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderStatusMappingController extends Controller
{
    /**
     * Get order status mapping configuration for a location
     */
    public function getStatusMapping(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $businessId = $request->session()->get('user.business_id');
            $locationId = $request->input('location_id');

            if (!$locationId) {
                return response()->json(['error' => 'Location ID is required'], 400);
            }

            $locationSetting = WoocommerceLocationSetting::where('business_id', $businessId)
                                                        ->where('location_id', $locationId)
                                                        ->first();

            if (!$locationSetting) {
                // Create a default location setting if it doesn't exist
                $locationSetting = WoocommerceLocationSetting::create([
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'is_active' => false,
                    'enable_bidirectional_sync' => true,
                    'auto_finalize_pos_sales' => true,
                    'auto_update_woo_status' => true,
                    'create_draft_on_webhook' => true
                ]);
            }

            // Get current mapping or use defaults
            $currentMapping = $locationSetting->order_status_mapping ?? $this->getDefaultStatusMapping();
            
            // Get available presets
            $presets = DB::table('woocommerce_order_status_presets')
                        ->where('is_active', true)
                        ->select(['id', 'name', 'description', 'mapping_configuration', 'is_default'])
                        ->get();

            return response()->json([
                'success' => true,
                'location_id' => $locationId,
                'current_mapping' => $currentMapping,
                'bidirectional_settings' => [
                    'enable_bidirectional_sync' => $locationSetting->enable_bidirectional_sync ?? true,
                    'auto_finalize_pos_sales' => $locationSetting->auto_finalize_pos_sales ?? true,
                    'auto_update_woo_status' => $locationSetting->auto_update_woo_status ?? true,
                    'create_draft_on_webhook' => $locationSetting->create_draft_on_webhook ?? true
                ],
                'presets' => $presets,
                'available_statuses' => $this->getAvailableStatuses(),
                'pos_invoice_types' => $this->getPosInvoiceTypes()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get status mapping configuration', [
                'error' => $e->getMessage(),
                'location_id' => $request->input('location_id')
            ]);

            return response()->json(['error' => 'Failed to load configuration'], 500);
        }
    }

    /**
     * Update order status mapping configuration
     */
    public function updateStatusMapping(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $validator = Validator::make($request->all(), [
                'location_id' => 'required|integer|exists:business_locations,id',
                'status_mapping' => 'required|array',
                'status_mapping.*' => 'required|in:draft,proforma,final,cancelled,refunded',
                'enable_bidirectional_sync' => 'boolean',
                'auto_finalize_pos_sales' => 'boolean',
                'auto_update_woo_status' => 'boolean',
                'create_draft_on_webhook' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $businessId = $request->session()->get('user.business_id');
            $locationId = $request->input('location_id');

            $locationSetting = WoocommerceLocationSetting::where('business_id', $businessId)
                                                        ->where('location_id', $locationId)
                                                        ->first();

            if (!$locationSetting) {
                return response()->json(['error' => 'WooCommerce settings not found for this location'], 404);
            }

            // Update the configuration
            $locationSetting->update([
                'order_status_mapping' => $request->input('status_mapping'),
                'enable_bidirectional_sync' => $request->input('enable_bidirectional_sync', true),
                'auto_finalize_pos_sales' => $request->input('auto_finalize_pos_sales', true),
                'auto_update_woo_status' => $request->input('auto_update_woo_status', true),
                'create_draft_on_webhook' => $request->input('create_draft_on_webhook', true)
            ]);

            Log::info('Order status mapping updated', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'mapping' => $request->input('status_mapping')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status mapping configuration updated successfully',
                'updated_mapping' => $locationSetting->order_status_mapping
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update status mapping', [
                'error' => $e->getMessage(),
                'location_id' => $request->input('location_id')
            ]);

            return response()->json(['error' => 'Failed to update configuration'], 500);
        }
    }

    /**
     * Apply a preset configuration
     */
    public function applyPreset(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $validator = Validator::make($request->all(), [
                'location_id' => 'required|integer|exists:business_locations,id',
                'preset_id' => 'required|integer|exists:woocommerce_order_status_presets,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $businessId = $request->session()->get('user.business_id');
            $locationId = $request->input('location_id');
            $presetId = $request->input('preset_id');

            // Get the preset configuration
            $preset = DB::table('woocommerce_order_status_presets')
                       ->where('id', $presetId)
                       ->where('is_active', true)
                       ->first();

            if (!$preset) {
                return response()->json(['error' => 'Preset not found'], 404);
            }

            $locationSetting = WoocommerceLocationSetting::where('business_id', $businessId)
                                                        ->where('location_id', $locationId)
                                                        ->first();

            if (!$locationSetting) {
                return response()->json(['error' => 'WooCommerce settings not found for this location'], 404);
            }

            // Apply the preset
            $presetMapping = json_decode($preset->mapping_configuration, true);
            $locationSetting->update([
                'order_status_mapping' => $presetMapping
            ]);

            Log::info('Order status preset applied', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'preset_name' => $preset->name,
                'mapping' => $presetMapping
            ]);

            return response()->json([
                'success' => true,
                'message' => "Preset '{$preset->name}' applied successfully",
                'applied_mapping' => $presetMapping
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to apply preset', [
                'error' => $e->getMessage(),
                'preset_id' => $request->input('preset_id')
            ]);

            return response()->json(['error' => 'Failed to apply preset'], 500);
        }
    }

    /**
     * Test status mapping configuration
     */
    public function testStatusMapping(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $validator = Validator::make($request->all(), [
                'location_id' => 'required|integer|exists:business_locations,id',
                'woocommerce_status' => 'required|string|in:pending,on-hold,processing,completed,cancelled,refunded,failed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $businessId = $request->session()->get('user.business_id');
            $locationId = $request->input('location_id');
            $wooStatus = $request->input('woocommerce_status');

            $locationSetting = WoocommerceLocationSetting::where('business_id', $businessId)
                                                        ->where('location_id', $locationId)
                                                        ->first();

            if (!$locationSetting) {
                return response()->json(['error' => 'WooCommerce settings not found for this location'], 404);
            }

            $statusMapping = $locationSetting->order_status_mapping ?? $this->getDefaultStatusMapping();
            $posInvoiceType = $statusMapping[$wooStatus] ?? 'draft';

            return response()->json([
                'success' => true,
                'test_result' => [
                    'woocommerce_status' => $wooStatus,
                    'pos_invoice_type' => $posInvoiceType,
                    'description' => $this->getInvoiceTypeDescription($posInvoiceType),
                    'actions' => $this->getExpectedActions($wooStatus, $posInvoiceType, $locationSetting)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to test status mapping', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to test mapping'], 500);
        }
    }

    /**
     * Get webhook event history
     */
    public function getWebhookHistory(Request $request): JsonResponse
    {
        // Check permissions
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $businessId = $request->session()->get('user.business_id');
            $locationId = $request->input('location_id');
            $limit = $request->input('limit', 50);

            $query = DB::table('woocommerce_webhook_events')
                      ->where('business_id', $businessId)
                      ->select([
                          'id', 'event_type', 'woocommerce_order_id', 'pos_transaction_id', 
                          'status', 'error_message', 'processed_at', 'created_at'
                      ])
                      ->orderBy('created_at', 'desc')
                      ->limit($limit);

            if ($locationId) {
                $query->where('location_id', $locationId);
            }

            $events = $query->get();

            return response()->json([
                'success' => true,
                'events' => $events,
                'summary' => [
                    'total_events' => $events->count(),
                    'successful' => $events->where('status', 'completed')->count(),
                    'failed' => $events->where('status', 'failed')->count(),
                    'pending' => $events->where('status', 'pending')->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get webhook history', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to load webhook history'], 500);
        }
    }

    /**
     * Get default status mapping
     */
    private function getDefaultStatusMapping(): array
    {
        return [
            'pending' => 'draft',
            'on-hold' => 'draft',
            'processing' => 'proforma', 
            'completed' => 'final',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'draft'
        ];
    }

    /**
     * Get available WooCommerce order statuses
     */
    private function getAvailableStatuses(): array
    {
        return [
            'pending' => 'Pending Payment',
            'on-hold' => 'On Hold',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'failed' => 'Failed'
        ];
    }

    /**
     * Get available POS invoice types
     */
    private function getPosInvoiceTypes(): array
    {
        return [
            'draft' => 'Draft Sale',
            'proforma' => 'Proforma Invoice',
            'final' => 'Final Invoice',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded'
        ];
    }

    /**
     * Get invoice type description
     */
    private function getInvoiceTypeDescription(string $type): string
    {
        $descriptions = [
            'draft' => 'Draft sale that can be edited and modified',
            'proforma' => 'Proforma invoice pending finalization', 
            'final' => 'Final invoice that affects inventory and accounting',
            'cancelled' => 'Cancelled transaction',
            'refunded' => 'Refunded transaction'
        ];

        return $descriptions[$type] ?? 'Unknown type';
    }

    /**
     * Get expected actions for status combination
     */
    private function getExpectedActions(string $wooStatus, string $posType, $locationSetting): array
    {
        $actions = [];

        // Determine what will happen when this status is received
        switch ($posType) {
            case 'draft':
                $actions[] = 'Create or update draft sale in POS';
                if ($locationSetting->create_draft_on_webhook) {
                    $actions[] = 'Automatically create draft if order doesn\'t exist';
                }
                break;
                
            case 'proforma':
                $actions[] = 'Convert to proforma invoice in POS';
                $actions[] = 'Update inventory allocation but don\'t affect stock levels';
                break;
                
            case 'final':
                $actions[] = 'Finalize sale in POS and affect inventory';
                $actions[] = 'Update accounting records';
                if ($locationSetting->auto_finalize_pos_sales) {
                    $actions[] = 'Automatically finalize if currently draft/proforma';
                }
                break;
                
            case 'cancelled':
                $actions[] = 'Cancel POS transaction';
                $actions[] = 'Restore any allocated inventory';
                break;
                
            case 'refunded':
                $actions[] = 'Create refund transaction in POS';
                $actions[] = 'Adjust inventory if needed';
                break;
        }

        if ($locationSetting->enable_bidirectional_sync) {
            $actions[] = 'Enable two-way status synchronization';
        }

        return $actions;
    }
}