<?php

namespace Modules\Woocommerce\Services;

use Automattic\WooCommerce\Client;
use App\Business;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Modules\Woocommerce\Exceptions\WooCommerceError;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class ModernWooCommerceClient
{
    private Client $client;
    private int $business_id;
    private ?int $location_id;
    private array $settings;
    private int $maxRetries = 3;
    private array $retryableErrorCodes = [429, 500, 502, 503, 504];
    
    public function __construct(int $business_id, ?int $location_id = null)
    {
        $this->business_id = $business_id;
        $this->location_id = $location_id;
        $this->settings = $this->getApiSettings($business_id, $location_id);
        $this->initializeClient();
    }
    
    /**
     * Initialize WooCommerce client with modern API v3 configuration
     */
    private function initializeClient(): void
    {
        if (empty($this->settings)) {
            throw new WooCommerceError(__('woocommerce::lang.unable_to_connect'));
        }
        
        $this->client = new Client(
            $this->settings['woocommerce_app_url'],
            $this->settings['woocommerce_consumer_key'],
            $this->settings['woocommerce_consumer_secret'],
            [
                'wp_api' => true,
                'version' => 'wc/v3',  // Upgraded from v2
                'timeout' => 30,       // Increased timeout
                'verify_ssl' => true,  // Enhanced security
                'query_string_auth' => true,
                'follow_redirects' => true,
                'user_agent' => 'IsleBooks POS/2.0 WooCommerce Integration',
                'debug' => config('app.debug', false)
            ]
        );
    }
    
    /**
     * Execute API request with retry mechanism and exponential backoff
     */
    public function withRetry(callable $operation, array $context = [])
    {
        $attempt = 0;
        $maxDelay = 60; // Max 60 seconds delay
        
        while ($attempt < $this->maxRetries) {
            try {
                $startTime = microtime(true);
                $result = $operation($this->client);
                
                // Log successful API call
                $this->logApiCall($context, microtime(true) - $startTime, true);
                
                return $result;
                
            } catch (Exception $e) {
                $attempt++;
                $isRetryable = $this->isRetryableError($e);
                
                // Log failed API call
                $this->logApiCall($context, microtime(true) - $startTime ?? 0, false, $e);
                
                if (!$isRetryable || $attempt >= $this->maxRetries) {
                    throw new WooCommerceError($this->formatErrorMessage($e));
                }
                
                // Exponential backoff with jitter
                $delay = min($maxDelay, (2 ** $attempt) + rand(0, 1000) / 1000);
                Log::info("WooCommerce API retry attempt {$attempt}/{$this->maxRetries} after {$delay}s delay", [
                    'business_id' => $this->business_id,
                    'error' => $e->getMessage()
                ]);
                
                sleep($delay);
            }
        }
    }
    
    /**
     * Enhanced batch processing with adaptive sizing
     */
    public function batchRequest(string $endpoint, array $data, string $operation = 'create', int $batchSize = 25): array
    {
        $results = [];
        $chunks = array_chunk($data, $batchSize);
        
        foreach ($chunks as $index => $chunk) {
            $batchData = [$operation => $chunk];
            
            $result = $this->withRetry(
                fn($client) => $client->post($endpoint . '/batch', $batchData),
                [
                    'endpoint' => $endpoint . '/batch',
                    'operation' => $operation,
                    'batch_size' => count($chunk),
                    'batch_index' => $index + 1,
                    'total_batches' => count($chunks)
                ]
            );
            
            $results[] = $result;
        }
        
        return $results;
    }
    
    /**
     * Get all items with improved pagination and caching
     */
    public function getAllItems(string $endpoint, array $params = [], bool $useCache = true): array
    {
        $cacheKey = "woocommerce:{$this->business_id}:{$endpoint}:" . md5(serialize($params));
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $allItems = [];
        $page = 1;
        $perPage = 100;
        $params['per_page'] = $perPage;
        
        do {
            $params['page'] = $page;
            
            $items = $this->withRetry(
                fn($client) => $client->get($endpoint, $params),
                [
                    'endpoint' => $endpoint,
                    'page' => $page,
                    'per_page' => $perPage
                ]
            );
            
            $allItems = array_merge($allItems, $items);
            $page++;
            
            // Safety break to prevent infinite loops
            if ($page > 1000) {
                Log::warning('WooCommerce pagination safety break triggered', [
                    'business_id' => $this->business_id,
                    'endpoint' => $endpoint,
                    'items_fetched' => count($allItems)
                ]);
                break;
            }
            
        } while (count($items) === $perPage);
        
        // Cache results for 5 minutes
        if ($useCache) {
            Cache::put($cacheKey, $allItems, 300);
        }
        
        return $allItems;
    }
    
    /**
     * Test API connectivity and return detailed status
     */
    public function testConnection(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test with a simple system status call
            $systemStatus = $this->withRetry(
                fn($client) => $client->get('system_status'),
                ['endpoint' => 'system_status', 'test' => true]
            );
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'connected' => true,
                'api_version' => $systemStatus->environment->version ?? 'Unknown',
                'response_time_ms' => $responseTime,
                'wp_version' => $systemStatus->environment->wp_version ?? 'Unknown',
                'php_version' => $systemStatus->environment->php_version ?? 'Unknown',
                'message' => 'Connection successful'
            ];
            
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'message' => 'Connection failed: ' . $this->formatErrorMessage($e)
            ];
        }
    }
    
    /**
     * Get API settings for business
     */
    private function getApiSettings(int $business_id, ?int $location_id = null): array
    {
        // If location_id provided, get location-specific settings
        if ($location_id) {
            $locationSettings = WoocommerceLocationSetting::getByLocation($business_id, $location_id);
            
            if ($locationSettings && $locationSettings->hasValidApiConfig()) {
                return [
                    'woocommerce_app_url' => $locationSettings->woocommerce_app_url,
                    'woocommerce_consumer_key' => $locationSettings->woocommerce_consumer_key,
                    'woocommerce_consumer_secret' => $locationSettings->woocommerce_consumer_secret,
                    'location_id' => $location_id
                ];
            }
            
            // If no valid location settings, return empty (don't fallback to business)
            return [];
        }
        
        // Legacy: business-level settings
        $business = Business::find($business_id);
        
        if (!$business || empty($business->woocommerce_api_settings)) {
            return [];
        }
        
        return json_decode($business->woocommerce_api_settings, true) ?? [];
    }
    
    /**
     * Check if error is retryable
     */
    private function isRetryableError(Exception $e): bool
    {
        // Check HTTP status codes
        if (method_exists($e, 'getCode') && in_array($e->getCode(), $this->retryableErrorCodes)) {
            return true;
        }
        
        // Check error message patterns
        $retryablePatterns = [
            'timeout',
            'connection',
            'network',
            'temporarily unavailable',
            'rate limit'
        ];
        
        $message = strtolower($e->getMessage());
        foreach ($retryablePatterns as $pattern) {
            if (strpos($message, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Format error message for user display
     */
    private function formatErrorMessage(Exception $e): string
    {
        $code = $e->getCode();
        $message = $e->getMessage();
        
        // Map common error codes to user-friendly messages
        $errorMappings = [
            401 => 'Invalid API credentials. Please check your Consumer Key and Consumer Secret.',
            403 => 'Access forbidden. Please verify your WooCommerce API permissions.',
            404 => 'WooCommerce API endpoint not found. Please check your store URL.',
            429 => 'API rate limit exceeded. Please try again later.',
            500 => 'WooCommerce server error. Please contact your store administrator.',
            502 => 'Bad gateway. Please check your WooCommerce installation.',
            503 => 'WooCommerce service temporarily unavailable.',
            504 => 'Gateway timeout. The request took too long to process.'
        ];
        
        if (isset($errorMappings[$code])) {
            return $errorMappings[$code];
        }
        
        return "API Error ({$code}): {$message}";
    }
    
    /**
     * Log API calls for monitoring and debugging
     */
    private function logApiCall(array $context, float $duration, bool $success, Exception $error = null): void
    {
        $logData = [
            'business_id' => $this->business_id,
            'endpoint' => $context['endpoint'] ?? 'unknown',
            'duration_ms' => round($duration * 1000, 2),
            'success' => $success,
            'timestamp' => now()->toISOString()
        ];
        
        if (!$success && $error) {
            $logData['error'] = $error->getMessage();
            $logData['error_code'] = $error->getCode();
        }
        
        if (isset($context['batch_size'])) {
            $logData['batch_size'] = $context['batch_size'];
            $logData['batch_index'] = $context['batch_index'];
        }
        
        if ($success) {
            Log::info('WooCommerce API call succeeded', $logData);
        } else {
            Log::error('WooCommerce API call failed', $logData);
        }
    }
    
    /**
     * Update a product in WooCommerce
     */
    public function updateProduct(int $woocommerce_product_id, array $data): ?array
    {
        return $this->withRetry(function() use ($woocommerce_product_id, $data) {
            try {
                $response = $this->client->put("products/{$woocommerce_product_id}", $data);
                
                $this->logApiCall('PUT', "products/{$woocommerce_product_id}", $data, true, 
                    strlen(json_encode($response)), microtime(true));
                
                return $response;
                
            } catch (Exception $e) {
                $this->logApiCall('PUT', "products/{$woocommerce_product_id}", $data, false, 
                    0, microtime(true), $e->getMessage());
                
                Log::error('Failed to update product in WooCommerce', [
                    'wc_product_id' => $woocommerce_product_id,
                    'data' => $data,
                    'error' => $e->getMessage()
                ]);
                
                return null;
            }
        });
    }
    
    /**
     * Update a product variation in WooCommerce
     */
    public function updateProductVariation(int $woocommerce_product_id, int $woocommerce_variation_id, array $data): ?array
    {
        return $this->withRetry(function() use ($woocommerce_product_id, $woocommerce_variation_id, $data) {
            try {
                $response = $this->client->put("products/{$woocommerce_product_id}/variations/{$woocommerce_variation_id}", $data);
                
                $this->logApiCall('PUT', "products/{$woocommerce_product_id}/variations/{$woocommerce_variation_id}", 
                    $data, true, strlen(json_encode($response)), microtime(true));
                
                return $response;
                
            } catch (Exception $e) {
                $this->logApiCall('PUT', "products/{$woocommerce_product_id}/variations/{$woocommerce_variation_id}", 
                    $data, false, 0, microtime(true), $e->getMessage());
                
                Log::error('Failed to update product variation in WooCommerce', [
                    'wc_product_id' => $woocommerce_product_id,
                    'wc_variation_id' => $woocommerce_variation_id,
                    'data' => $data,
                    'error' => $e->getMessage()
                ]);
                
                return null;
            }
        });
    }
    
    /**
     * Batch update products (for bulk stock updates)
     */
    public function batchUpdateProducts(array $products): ?array
    {
        return $this->withRetry(function() use ($products) {
            try {
                $batchData = [
                    'update' => $products
                ];
                
                $response = $this->client->post('products/batch', $batchData);
                
                $this->logApiCall('POST', 'products/batch', $batchData, true, 
                    strlen(json_encode($response)), microtime(true));
                
                return $response;
                
            } catch (Exception $e) {
                $this->logApiCall('POST', 'products/batch', $batchData, false, 
                    0, microtime(true), $e->getMessage());
                
                Log::error('Failed to batch update products in WooCommerce', [
                    'products_count' => count($products),
                    'error' => $e->getMessage()
                ]);
                
                return null;
            }
        });
    }

    /**
     * Get the underlying client for advanced operations
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}