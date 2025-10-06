<?php

namespace Modules\Woocommerce\Http\Controllers;

use App\Business;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Woocommerce\Jobs\WooCommerceWebhookJob;
use Modules\Woocommerce\Services\WebhookSecurityService;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class EnhancedWoocommerceWebhookController extends Controller
{
    protected WoocommerceUtil $woocommerceUtil;
    protected ModuleUtil $moduleUtil;
    protected TransactionUtil $transactionUtil;
    protected ProductUtil $productUtil;
    protected WebhookSecurityService $securityService;

    public function __construct(
        WoocommerceUtil $woocommerceUtil,
        ModuleUtil $moduleUtil,
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil,
        WebhookSecurityService $securityService
    ) {
        $this->woocommerceUtil = $woocommerceUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->securityService = $securityService;
    }

    /**
     * Enhanced webhook handler with comprehensive security and error handling
     */
    public function handleWebhook(Request $request, string $business_id, string $event): Response
    {
        $startTime = microtime(true);
        $webhookId = $this->generateWebhookId();
        
        // Enhanced logging
        Log::info('Webhook received', [
            'webhook_id' => $webhookId,
            'business_id' => $business_id,
            'event' => $event,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_length' => strlen($request->getContent())
        ]);

        try {
            // Rate limiting
            if (!$this->checkRateLimit($request, $business_id)) {
                Log::warning('Webhook rate limit exceeded', [
                    'webhook_id' => $webhookId,
                    'business_id' => $business_id,
                    'ip' => $request->ip()
                ]);
                return response('Rate limit exceeded', HttpResponse::HTTP_TOO_MANY_REQUESTS);
            }

            // Validate business exists and is active
            $business = $this->validateBusiness($business_id);
            if (!$business) {
                Log::error('Invalid business for webhook', [
                    'webhook_id' => $webhookId,
                    'business_id' => $business_id
                ]);
                return response('Business not found', HttpResponse::HTTP_NOT_FOUND);
            }

            // Enhanced security validation
            $securityResult = $this->securityService->validateWebhook($request, $business, $event);
            if (!$securityResult['valid']) {
                Log::error('Webhook security validation failed', [
                    'webhook_id' => $webhookId,
                    'business_id' => $business_id,
                    'reason' => $securityResult['reason'],
                    'details' => $securityResult['details']
                ]);
                return response('Unauthorized', HttpResponse::HTTP_UNAUTHORIZED);
            }

            // Replay attack protection
            if ($this->isReplayAttack($request, $webhookId)) {
                Log::warning('Potential replay attack detected', [
                    'webhook_id' => $webhookId,
                    'business_id' => $business_id
                ]);
                return response('Duplicate webhook', HttpResponse::HTTP_CONFLICT);
            }

            // Parse and validate payload
            $payload = $this->parseWebhookPayload($request);
            if (!$payload) {
                Log::error('Invalid webhook payload', [
                    'webhook_id' => $webhookId,
                    'business_id' => $business_id
                ]);
                return response('Invalid payload', HttpResponse::HTTP_BAD_REQUEST);
            }

            // Validate event type
            if (!$this->isValidEventType($event)) {
                Log::error('Unsupported webhook event', [
                    'webhook_id' => $webhookId,
                    'business_id' => $business_id,
                    'event' => $event
                ]);
                return response('Unsupported event type', HttpResponse::HTTP_BAD_REQUEST);
            }

            // Queue the webhook for background processing
            WooCommerceWebhookJob::dispatch($business_id, $event, $payload, [
                'webhook_id' => $webhookId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'received_at' => now()->toISOString()
            ])->onQueue('woocommerce-webhooks');

            // Log successful reception
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('Webhook queued successfully', [
                'webhook_id' => $webhookId,
                'business_id' => $business_id,
                'event' => $event,
                'processing_time_ms' => $processingTime
            ]);

            return response('Webhook received', HttpResponse::HTTP_OK);

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('Webhook handling failed', [
                'webhook_id' => $webhookId,
                'business_id' => $business_id,
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'processing_time_ms' => $processingTime
            ]);

            return response('Internal server error', HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Enhanced order created webhook with improved error handling
     */
    public function orderCreated(Request $request, string $business_id): Response
    {
        return $this->handleWebhook($request, $business_id, 'order.created');
    }

    /**
     * Enhanced order updated webhook
     */
    public function orderUpdated(Request $request, string $business_id): Response
    {
        return $this->handleWebhook($request, $business_id, 'order.updated');
    }

    /**
     * Enhanced order deleted webhook
     */
    public function orderDeleted(Request $request, string $business_id): Response
    {
        return $this->handleWebhook($request, $business_id, 'order.deleted');
    }

    /**
     * Enhanced order restored webhook
     */
    public function orderRestored(Request $request, string $business_id): Response
    {
        return $this->handleWebhook($request, $business_id, 'order.restored');
    }

    /**
     * Product created webhook
     */
    public function productCreated(Request $request, string $business_id): Response
    {
        return $this->handleWebhook($request, $business_id, 'product.created');
    }

    /**
     * Product updated webhook
     */
    public function productUpdated(Request $request, string $business_id): Response
    {
        return $this->handleWebhook($request, $business_id, 'product.updated');
    }

    /**
     * Product deleted webhook
     */
    public function productDeleted(Request $request, string $business_id): Response
    {
        return $this->handleWebhook($request, $business_id, 'product.deleted');
    }

    /**
     * Check rate limiting for webhook requests
     */
    protected function checkRateLimit(Request $request, string $business_id): bool
    {
        $key = "webhook_rate_limit:{$business_id}:{$request->ip()}";
        $maxAttempts = 100; // Max 100 webhooks per minute per IP per business
        $decayMinutes = 1;

        return RateLimiter::attempt($key, $maxAttempts, function () {
            // Rate limit passed
        }, $decayMinutes);
    }

    /**
     * Validate business exists and webhook module is enabled
     */
    protected function validateBusiness(string $business_id): ?Business
    {
        $business = Business::find($business_id);
        
        if (!$business) {
            return null;
        }

        // Check if WooCommerce module is enabled for this business
        if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module')) {
            Log::warning('WooCommerce module not enabled for business', [
                'business_id' => $business_id
            ]);
            return null;
        }

        return $business;
    }

    /**
     * Check for replay attacks using webhook signatures and timestamps
     */
    protected function isReplayAttack(Request $request, string $webhookId): bool
    {
        $signature = $request->header('x-wc-webhook-signature');
        $timestamp = $request->header('x-wc-webhook-timestamp', time());
        
        if (!$signature) {
            return false; // No signature to check
        }

        // Check if this exact signature was seen recently (within 5 minutes)
        $cacheKey = "webhook_signature:" . md5($signature);
        if (Cache::has($cacheKey)) {
            return true; // Potential replay attack
        }

        // Check timestamp age (reject webhooks older than 5 minutes)
        $maxAge = 300; // 5 minutes
        if (abs(time() - $timestamp) > $maxAge) {
            Log::warning('Webhook timestamp too old', [
                'webhook_id' => $webhookId,
                'timestamp' => $timestamp,
                'current_time' => time(),
                'age_seconds' => abs(time() - $timestamp)
            ]);
            return true; // Too old, likely replay
        }

        // Cache this signature for 5 minutes to prevent replays
        Cache::put($cacheKey, $webhookId, 300);

        return false;
    }

    /**
     * Parse and validate webhook payload
     */
    protected function parseWebhookPayload(Request $request): ?array
    {
        try {
            $content = $request->getContent();
            
            if (empty($content)) {
                return null;
            }

            $payload = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error in webhook payload', [
                    'error' => json_last_error_msg(),
                    'content_length' => strlen($content)
                ]);
                return null;
            }

            // Basic payload validation
            if (!is_array($payload) || empty($payload['id'])) {
                Log::error('Invalid webhook payload structure', [
                    'payload_type' => gettype($payload),
                    'has_id' => isset($payload['id'])
                ]);
                return null;
            }

            return $payload;

        } catch (\Exception $e) {
            Log::error('Failed to parse webhook payload', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate event type
     */
    protected function isValidEventType(string $event): bool
    {
        $validEvents = [
            'order.created',
            'order.updated', 
            'order.deleted',
            'order.restored',
            'product.created',
            'product.updated',
            'product.deleted',
            'customer.created',
            'customer.updated',
            'customer.deleted'
        ];

        return in_array($event, $validEvents);
    }

    /**
     * Generate unique webhook ID for tracking
     */
    protected function generateWebhookId(): string
    {
        return 'wh_' . uniqid() . '_' . mt_rand(1000, 9999);
    }

    /**
     * Get webhook health status for monitoring
     */
    public function healthCheck(Request $request, string $business_id): Response
    {
        try {
            $business = $this->validateBusiness($business_id);
            
            if (!$business) {
                return response()->json(['status' => 'error', 'message' => 'Business not found'], 404);
            }

            // Check webhook configuration
            $hasSecrets = !empty($business->woocommerce_wh_oc_secret) ||
                         !empty($business->woocommerce_wh_ou_secret) ||
                         !empty($business->woocommerce_wh_od_secret) ||
                         !empty($business->woocommerce_wh_or_secret);

            return response()->json([
                'status' => 'healthy',
                'business_id' => $business_id,
                'webhook_secrets_configured' => $hasSecrets,
                'module_enabled' => true,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}