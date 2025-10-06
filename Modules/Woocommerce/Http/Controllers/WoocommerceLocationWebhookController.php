<?php

namespace Modules\Woocommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use Modules\Woocommerce\Utils\BidirectionalSyncManager;
use Illuminate\Support\Facades\Log;
use App\BusinessLocation;
use Exception;

class WoocommerceLocationWebhookController extends Controller
{
    private $woocommerceUtil;

    public function __construct(WoocommerceUtil $woocommerceUtil)
    {
        $this->woocommerceUtil = $woocommerceUtil;
    }

    /**
     * Handle location-specific webhooks from WooCommerce
     */
    public function handleWebhook(Request $request, $location_id)
    {
        try {
            Log::info('WooCommerce location webhook received', [
                'location_id' => $location_id,
                'headers' => $request->headers->all(),
                'payload_size' => strlen($request->getContent())
            ]);

            // Get location configuration
            $location = BusinessLocation::findOrFail($location_id);
            $locationSetting = WoocommerceLocationSetting::where('location_id', $location_id)
                                                          ->where('business_id', $location->business_id)
                                                          ->first();

            if (!$locationSetting || !$locationSetting->is_active) {
                Log::warning('Webhook received for inactive or unconfigured location', [
                    'location_id' => $location_id,
                    'has_setting' => !is_null($locationSetting),
                    'is_active' => $locationSetting ? $locationSetting->is_active : false
                ]);
                return response('Location not configured or inactive', 404);
            }

            // Verify webhook signature
            if (!$this->verifyWebhookSignature($request, $locationSetting->webhook_secret)) {
                Log::warning('Invalid webhook signature for location', [
                    'location_id' => $location_id
                ]);
                return response('Invalid signature', 401);
            }

            // Get webhook event type from headers
            $eventType = $request->header('X-WC-Webhook-Event', 'unknown');
            $resourceType = $request->header('X-WC-Webhook-Resource', 'unknown');
            
            Log::info('Processing webhook event', [
                'location_id' => $location_id,
                'event_type' => $eventType,
                'resource_type' => $resourceType
            ]);

            // Route to appropriate handler based on event type
            $response = $this->routeWebhookEvent($request, $locationSetting, $eventType, $resourceType);

            return $response;

        } catch (Exception $e) {
            Log::error('Webhook processing failed', [
                'location_id' => $location_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Internal server error', 500);
        }
    }

    /**
     * Verify webhook signature using HMAC-SHA256
     */
    private function verifyWebhookSignature(Request $request, string $webhookSecret): bool
    {
        $signature = $request->header('X-WC-Webhook-Signature');
        
        if (empty($signature) || empty($webhookSecret)) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $webhookSecret, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Route webhook event to appropriate handler
     */
    private function routeWebhookEvent(Request $request, WoocommerceLocationSetting $locationSetting, string $eventType, string $resourceType)
    {
        $payload = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON payload in webhook', [
                'location_id' => $locationSetting->location_id,
                'json_error' => json_last_error_msg()
            ]);
            return response('Invalid JSON payload', 400);
        }

        try {
            switch ($resourceType) {
                case 'order':
                    return $this->handleOrderWebhook($locationSetting, $eventType, $payload);
                    
                case 'product':
                    return $this->handleProductWebhook($locationSetting, $eventType, $payload);
                    
                case 'customer':
                    return $this->handleCustomerWebhook($locationSetting, $eventType, $payload);
                    
                default:
                    Log::info('Unhandled webhook resource type', [
                        'location_id' => $locationSetting->location_id,
                        'resource_type' => $resourceType,
                        'event_type' => $eventType
                    ]);
                    return response('Resource type not handled', 200);
            }
        } catch (Exception $e) {
            Log::error('Webhook event processing failed', [
                'location_id' => $locationSetting->location_id,
                'resource_type' => $resourceType,
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle order-related webhooks
     */
    private function handleOrderWebhook(WoocommerceLocationSetting $locationSetting, string $eventType, array $payload)
    {
        Log::info('Processing order webhook', [
            'location_id' => $locationSetting->location_id,
            'event_type' => $eventType,
            'order_id' => $payload['id'] ?? 'unknown'
        ]);

        switch ($eventType) {
            case 'created':
                return $this->processOrderCreated($locationSetting, $payload);
                
            case 'updated':
                return $this->processOrderUpdated($locationSetting, $payload);
                
            case 'deleted':
                return $this->processOrderDeleted($locationSetting, $payload);
                
            default:
                Log::info('Unhandled order event type', [
                    'location_id' => $locationSetting->location_id,
                    'event_type' => $eventType
                ]);
                return response('Order event type not handled', 200);
        }
    }

    /**
     * Handle product-related webhooks
     */
    private function handleProductWebhook(WoocommerceLocationSetting $locationSetting, string $eventType, array $payload)
    {
        Log::info('Processing product webhook', [
            'location_id' => $locationSetting->location_id,
            'event_type' => $eventType,
            'product_id' => $payload['id'] ?? 'unknown'
        ]);

        if (!$locationSetting->sync_products) {
            Log::info('Product sync disabled for location', [
                'location_id' => $locationSetting->location_id
            ]);
            return response('Product sync disabled', 200);
        }

        switch ($eventType) {
            case 'created':
            case 'updated':
                return $this->processProductSync($locationSetting, $payload);
                
            case 'deleted':
                return $this->processProductDeleted($locationSetting, $payload);
                
            default:
                Log::info('Unhandled product event type', [
                    'location_id' => $locationSetting->location_id,
                    'event_type' => $eventType
                ]);
                return response('Product event type not handled', 200);
        }
    }

    /**
     * Handle customer-related webhooks
     */
    private function handleCustomerWebhook(WoocommerceLocationSetting $locationSetting, string $eventType, array $payload)
    {
        Log::info('Processing customer webhook', [
            'location_id' => $locationSetting->location_id,
            'event_type' => $eventType,
            'customer_id' => $payload['id'] ?? 'unknown'
        ]);

        if (!$locationSetting->sync_customers) {
            Log::info('Customer sync disabled for location', [
                'location_id' => $locationSetting->location_id
            ]);
            return response('Customer sync disabled', 200);
        }

        switch ($eventType) {
            case 'created':
            case 'updated':
                return $this->processCustomerSync($locationSetting, $payload);
                
            case 'deleted':
                return $this->processCustomerDeleted($locationSetting, $payload);
                
            default:
                Log::info('Unhandled customer event type', [
                    'location_id' => $locationSetting->location_id,
                    'event_type' => $eventType
                ]);
                return response('Customer event type not handled', 200);
        }
    }

    /**
     * Process order created webhook
     */
    private function processOrderCreated(WoocommerceLocationSetting $locationSetting, array $orderData)
    {
        try {
            // Check if order sync is enabled
            if (!$locationSetting->sync_orders) {
                return response('Order sync disabled', 200);
            }

            Log::info('Processing order created webhook', [
                'location_id' => $locationSetting->location_id,
                'order_id' => $orderData['id'],
                'order_status' => $orderData['status'] ?? 'unknown'
            ]);

            // Use BidirectionalSyncManager to handle the order creation
            $result = BidirectionalSyncManager::processOrderWebhook(
                [
                    'event_type' => 'order.created',
                    'order' => $orderData
                ],
                $locationSetting->business_id,
                $locationSetting->location_id
            );

            if ($result['success']) {
                Log::info('Order created webhook processed successfully', [
                    'location_id' => $locationSetting->location_id,
                    'order_id' => $orderData['id'],
                    'result' => $result
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Order created and synced to POS',
                    'action' => $result['action'] ?? 'processed',
                    'pos_transaction_id' => $result['transaction_id'] ?? null
                ], 200);
            } else {
                Log::warning('Order created webhook processing failed', [
                    'location_id' => $locationSetting->location_id,
                    'order_id' => $orderData['id'],
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Processing failed'
                ], 422);
            }

        } catch (Exception $e) {
            Log::error('Failed to process order created webhook', [
                'location_id' => $locationSetting->location_id,
                'order_id' => $orderData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process order updated webhook
     */
    private function processOrderUpdated(WoocommerceLocationSetting $locationSetting, array $orderData)
    {
        try {
            if (!$locationSetting->sync_orders) {
                return response('Order sync disabled', 200);
            }

            Log::info('Processing order updated webhook', [
                'location_id' => $locationSetting->location_id,
                'order_id' => $orderData['id'],
                'order_status' => $orderData['status'] ?? 'unknown'
            ]);

            // Use BidirectionalSyncManager to handle the order update
            // This is the KEY functionality you requested - when order status changes in WooCommerce,
            // it should automatically update/finalize the POS sale
            $result = BidirectionalSyncManager::processOrderWebhook(
                [
                    'event_type' => 'order.updated', 
                    'order' => $orderData
                ],
                $locationSetting->business_id,
                $locationSetting->location_id
            );

            if ($result['success']) {
                Log::info('Order updated webhook processed successfully', [
                    'location_id' => $locationSetting->location_id,
                    'order_id' => $orderData['id'],
                    'action' => $result['action'] ?? 'processed',
                    'result' => $result
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order status change synced to POS',
                    'action' => $result['action'] ?? 'processed',
                    'pos_transaction_id' => $result['transaction_id'] ?? null,
                    'woo_status' => $orderData['status'] ?? 'unknown'
                ], 200);
            } else {
                Log::warning('Order updated webhook processing failed', [
                    'location_id' => $locationSetting->location_id,
                    'order_id' => $orderData['id'],
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                return response()->json([
                    'status' => 'error', 
                    'message' => $result['error'] ?? 'Processing failed'
                ], 422);
            }

        } catch (Exception $e) {
            Log::error('Failed to process order updated webhook', [
                'location_id' => $locationSetting->location_id,
                'order_id' => $orderData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process order deleted webhook
     */
    private function processOrderDeleted(WoocommerceLocationSetting $locationSetting, array $orderData)
    {
        try {
            if (!$locationSetting->sync_orders) {
                return response('Order sync disabled', 200);
            }

            Log::info('Processing order deleted webhook', [
                'location_id' => $locationSetting->location_id,
                'order_id' => $orderData['id']
            ]);

            // Find and cancel the corresponding POS transaction
            $transaction = \App\Transaction::where('business_id', $locationSetting->business_id)
                                          ->where('woocommerce_order_id', $orderData['id'])
                                          ->first();

            if ($transaction) {
                $transaction->update(['status' => 'cancelled']);
                
                Log::info('POS transaction cancelled due to WooCommerce order deletion', [
                    'location_id' => $locationSetting->location_id,
                    'woo_order_id' => $orderData['id'],
                    'pos_transaction_id' => $transaction->id
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order deleted and POS transaction cancelled',
                    'pos_transaction_id' => $transaction->id
                ], 200);
            } else {
                Log::info('No corresponding POS transaction found for deleted WooCommerce order', [
                    'location_id' => $locationSetting->location_id,
                    'woo_order_id' => $orderData['id']
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order deleted (no corresponding POS transaction found)'
                ], 200);
            }

        } catch (Exception $e) {
            Log::error('Failed to process order deleted webhook', [
                'location_id' => $locationSetting->location_id,
                'order_id' => $orderData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process product sync webhook
     */
    private function processProductSync(WoocommerceLocationSetting $locationSetting, array $productData)
    {
        try {
            Log::info('Product sync webhook processed', [
                'location_id' => $locationSetting->location_id,
                'product_id' => $productData['id']
            ]);

            return response('Product sync processed', 200);

        } catch (Exception $e) {
            Log::error('Failed to process product sync webhook', [
                'location_id' => $locationSetting->location_id,
                'product_id' => $productData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process product deleted webhook
     */
    private function processProductDeleted(WoocommerceLocationSetting $locationSetting, array $productData)
    {
        try {
            Log::info('Product deleted webhook processed', [
                'location_id' => $locationSetting->location_id,
                'product_id' => $productData['id']
            ]);

            return response('Product deleted processed', 200);

        } catch (Exception $e) {
            Log::error('Failed to process product deleted webhook', [
                'location_id' => $locationSetting->location_id,
                'product_id' => $productData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process customer sync webhook
     */
    private function processCustomerSync(WoocommerceLocationSetting $locationSetting, array $customerData)
    {
        try {
            Log::info('Customer sync webhook processed', [
                'location_id' => $locationSetting->location_id,
                'customer_id' => $customerData['id']
            ]);

            return response('Customer sync processed', 200);

        } catch (Exception $e) {
            Log::error('Failed to process customer sync webhook', [
                'location_id' => $locationSetting->location_id,
                'customer_id' => $customerData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process customer deleted webhook
     */
    private function processCustomerDeleted(WoocommerceLocationSetting $locationSetting, array $customerData)
    {
        try {
            Log::info('Customer deleted webhook processed', [
                'location_id' => $locationSetting->location_id,
                'customer_id' => $customerData['id']
            ]);

            return response('Customer deleted processed', 200);

        } catch (Exception $e) {
            Log::error('Failed to process customer deleted webhook', [
                'location_id' => $locationSetting->location_id,
                'customer_id' => $customerData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}