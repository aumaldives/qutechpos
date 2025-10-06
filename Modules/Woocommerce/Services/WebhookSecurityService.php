<?php

namespace Modules\Woocommerce\Services;

use App\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebhookSecurityService
{
    /**
     * Validate webhook request with enhanced security checks
     */
    public function validateWebhook(Request $request, Business $business, string $event): array
    {
        $validationResults = [
            'valid' => false,
            'reason' => '',
            'details' => []
        ];

        try {
            // 1. Signature validation
            $signatureResult = $this->validateSignature($request, $business, $event);
            if (!$signatureResult['valid']) {
                return array_merge($validationResults, [
                    'reason' => 'signature_validation_failed',
                    'details' => $signatureResult
                ]);
            }

            // 2. IP whitelist validation (if configured)
            $ipResult = $this->validateSourceIP($request, $business);
            if (!$ipResult['valid']) {
                return array_merge($validationResults, [
                    'reason' => 'ip_validation_failed',
                    'details' => $ipResult
                ]);
            }

            // 3. Timestamp validation
            $timestampResult = $this->validateTimestamp($request);
            if (!$timestampResult['valid']) {
                return array_merge($validationResults, [
                    'reason' => 'timestamp_validation_failed',
                    'details' => $timestampResult
                ]);
            }

            // 4. Content validation
            $contentResult = $this->validateContent($request);
            if (!$contentResult['valid']) {
                return array_merge($validationResults, [
                    'reason' => 'content_validation_failed',
                    'details' => $contentResult
                ]);
            }

            // 5. Event-specific validation
            $eventResult = $this->validateEventData($request, $event);
            if (!$eventResult['valid']) {
                return array_merge($validationResults, [
                    'reason' => 'event_validation_failed',
                    'details' => $eventResult
                ]);
            }

            return [
                'valid' => true,
                'reason' => 'validation_passed',
                'details' => [
                    'signature' => $signatureResult,
                    'ip' => $ipResult,
                    'timestamp' => $timestampResult,
                    'content' => $contentResult,
                    'event' => $eventResult
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Webhook security validation error', [
                'business_id' => $business->id,
                'event' => $event,
                'error' => $e->getMessage()
            ]);

            return array_merge($validationResults, [
                'reason' => 'validation_exception',
                'details' => ['error' => $e->getMessage()]
            ]);
        }
    }

    /**
     * Enhanced signature validation with timing attack protection
     */
    protected function validateSignature(Request $request, Business $business, string $event): array
    {
        $signature = $request->header('x-wc-webhook-signature');
        $payload = $request->getContent();

        if (empty($signature)) {
            return [
                'valid' => false,
                'reason' => 'missing_signature',
                'signature_provided' => false
            ];
        }

        // Get the appropriate secret for this event
        $secret = $this->getWebhookSecret($business, $event);
        
        if (empty($secret)) {
            return [
                'valid' => false,
                'reason' => 'secret_not_configured',
                'event' => $event
            ];
        }

        // Calculate expected signature
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        // Use hash_equals for timing attack protection
        $isValid = hash_equals($expectedSignature, $signature);

        return [
            'valid' => $isValid,
            'reason' => $isValid ? 'signature_valid' : 'signature_mismatch',
            'signature_provided' => true,
            'signature_length' => strlen($signature),
            'payload_length' => strlen($payload)
        ];
    }

    /**
     * Validate source IP against whitelist (if configured)
     */
    protected function validateSourceIP(Request $request, Business $business): array
    {
        $clientIP = $request->ip();
        
        // Get IP whitelist from business settings (if configured)
        $apiSettings = json_decode($business->woocommerce_api_settings, true) ?? [];
        $ipWhitelist = $apiSettings['webhook_ip_whitelist'] ?? [];

        // If no whitelist configured, allow all IPs
        if (empty($ipWhitelist)) {
            return [
                'valid' => true,
                'reason' => 'no_whitelist_configured',
                'client_ip' => $clientIP
            ];
        }

        // Check if IP is in whitelist
        $isAllowed = false;
        foreach ($ipWhitelist as $allowedIP) {
            if ($this->ipMatches($clientIP, $allowedIP)) {
                $isAllowed = true;
                break;
            }
        }

        return [
            'valid' => $isAllowed,
            'reason' => $isAllowed ? 'ip_whitelisted' : 'ip_not_whitelisted',
            'client_ip' => $clientIP,
            'whitelist' => $ipWhitelist
        ];
    }

    /**
     * Validate webhook timestamp to prevent replay attacks
     */
    protected function validateTimestamp(Request $request): array
    {
        $timestamp = $request->header('x-wc-webhook-timestamp');
        $currentTime = time();
        
        if (empty($timestamp)) {
            // If no timestamp provided, use current time but log warning
            Log::warning('Webhook received without timestamp header');
            return [
                'valid' => true,
                'reason' => 'no_timestamp_provided',
                'timestamp' => null,
                'age_seconds' => null
            ];
        }

        $timestamp = (int) $timestamp;
        $ageSeconds = abs($currentTime - $timestamp);
        $maxAgeSeconds = 300; // 5 minutes tolerance

        $isValid = $ageSeconds <= $maxAgeSeconds;

        return [
            'valid' => $isValid,
            'reason' => $isValid ? 'timestamp_valid' : 'timestamp_too_old',
            'timestamp' => $timestamp,
            'current_time' => $currentTime,
            'age_seconds' => $ageSeconds,
            'max_age_seconds' => $maxAgeSeconds
        ];
    }

    /**
     * Validate webhook content
     */
    protected function validateContent(Request $request): array
    {
        $content = $request->getContent();
        $contentLength = strlen($content);
        
        // Check minimum content length
        if ($contentLength < 10) {
            return [
                'valid' => false,
                'reason' => 'content_too_short',
                'content_length' => $contentLength
            ];
        }

        // Check maximum content length (prevent DoS)
        $maxLength = 1024 * 1024; // 1MB max
        if ($contentLength > $maxLength) {
            return [
                'valid' => false,
                'reason' => 'content_too_large',
                'content_length' => $contentLength,
                'max_length' => $maxLength
            ];
        }

        // Validate JSON structure
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'reason' => 'invalid_json',
                'json_error' => json_last_error_msg(),
                'content_length' => $contentLength
            ];
        }

        return [
            'valid' => true,
            'reason' => 'content_valid',
            'content_length' => $contentLength,
            'json_valid' => true
        ];
    }

    /**
     * Validate event-specific data structure
     */
    protected function validateEventData(Request $request, string $event): array
    {
        $payload = json_decode($request->getContent(), true);
        
        if (!is_array($payload)) {
            return [
                'valid' => false,
                'reason' => 'payload_not_array'
            ];
        }

        // Check required fields based on event type
        $requiredFields = $this->getRequiredFieldsForEvent($event);
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return [
                'valid' => false,
                'reason' => 'missing_required_fields',
                'missing_fields' => $missingFields,
                'required_fields' => $requiredFields
            ];
        }

        // Event-specific validations
        $eventValidation = $this->performEventSpecificValidation($payload, $event);
        
        return [
            'valid' => $eventValidation['valid'],
            'reason' => $eventValidation['valid'] ? 'event_data_valid' : $eventValidation['reason'],
            'event' => $event,
            'payload_keys' => array_keys($payload),
            'event_validation' => $eventValidation
        ];
    }

    /**
     * Get webhook secret for specific event type
     */
    protected function getWebhookSecret(Business $business, string $event): ?string
    {
        $secretMapping = [
            'order.created' => $business->woocommerce_wh_oc_secret,
            'order.updated' => $business->woocommerce_wh_ou_secret,
            'order.deleted' => $business->woocommerce_wh_od_secret,
            'order.restored' => $business->woocommerce_wh_or_secret,
            'product.created' => $business->woocommerce_wh_pc_secret ?? $business->woocommerce_wh_oc_secret,
            'product.updated' => $business->woocommerce_wh_pu_secret ?? $business->woocommerce_wh_ou_secret,
            'product.deleted' => $business->woocommerce_wh_pd_secret ?? $business->woocommerce_wh_od_secret,
        ];

        return $secretMapping[$event] ?? null;
    }

    /**
     * Check if IP matches whitelist pattern (supports CIDR notation)
     */
    protected function ipMatches(string $clientIP, string $allowedIP): bool
    {
        // Direct match
        if ($clientIP === $allowedIP) {
            return true;
        }

        // CIDR notation support
        if (strpos($allowedIP, '/') !== false) {
            return $this->ipInCIDR($clientIP, $allowedIP);
        }

        // Wildcard support (e.g., 192.168.1.*)
        if (strpos($allowedIP, '*') !== false) {
            $pattern = str_replace('*', '.*', preg_quote($allowedIP, '/'));
            return preg_match('/^' . $pattern . '$/', $clientIP);
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInCIDR(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
        }
        
        // IPv6 support would go here if needed
        return false;
    }

    /**
     * Get required fields for event type
     */
    protected function getRequiredFieldsForEvent(string $event): array
    {
        $fieldMapping = [
            'order.created' => ['id', 'status', 'total'],
            'order.updated' => ['id', 'status'],
            'order.deleted' => ['id'],
            'order.restored' => ['id', 'status'],
            'product.created' => ['id', 'name'],
            'product.updated' => ['id', 'name'],
            'product.deleted' => ['id'],
            'customer.created' => ['id', 'email'],
            'customer.updated' => ['id', 'email'],
            'customer.deleted' => ['id']
        ];

        return $fieldMapping[$event] ?? ['id'];
    }

    /**
     * Perform event-specific validation
     */
    protected function performEventSpecificValidation(array $payload, string $event): array
    {
        switch ($event) {
            case 'order.created':
            case 'order.updated':
                return $this->validateOrderData($payload);
                
            case 'product.created':
            case 'product.updated':
                return $this->validateProductData($payload);
                
            default:
                return ['valid' => true, 'reason' => 'no_specific_validation'];
        }
    }

    /**
     * Validate order-specific data
     */
    protected function validateOrderData(array $payload): array
    {
        // Validate order ID is numeric
        if (!is_numeric($payload['id'])) {
            return [
                'valid' => false,
                'reason' => 'invalid_order_id',
                'order_id' => $payload['id']
            ];
        }

        // Validate status is a valid WooCommerce order status
        $validStatuses = [
            'pending', 'processing', 'on-hold', 'completed', 
            'cancelled', 'refunded', 'failed', 'trash'
        ];
        
        if (isset($payload['status']) && !in_array($payload['status'], $validStatuses)) {
            return [
                'valid' => false,
                'reason' => 'invalid_order_status',
                'status' => $payload['status'],
                'valid_statuses' => $validStatuses
            ];
        }

        return ['valid' => true, 'reason' => 'order_data_valid'];
    }

    /**
     * Validate product-specific data
     */
    protected function validateProductData(array $payload): array
    {
        // Validate product ID is numeric
        if (!is_numeric($payload['id'])) {
            return [
                'valid' => false,
                'reason' => 'invalid_product_id',
                'product_id' => $payload['id']
            ];
        }

        // Validate name is not empty
        if (isset($payload['name']) && empty(trim($payload['name']))) {
            return [
                'valid' => false,
                'reason' => 'empty_product_name'
            ];
        }

        return ['valid' => true, 'reason' => 'product_data_valid'];
    }
}