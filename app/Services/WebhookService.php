<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Jobs\DeliverWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class WebhookService
{
    /**
     * Dispatch a webhook event to all subscribed endpoints
     */
    public function dispatch(string $event, array $data, int $businessId): void
    {
        try {
            // Get all active webhooks for this business that are subscribed to this event
            $webhooks = Webhook::where('business_id', $businessId)
                              ->active()
                              ->forEvent($event)
                              ->get();
            
            if ($webhooks->isEmpty()) {
                Log::debug("No webhooks found for event: {$event} in business: {$businessId}");
                return;
            }
            
            foreach ($webhooks as $webhook) {
                $this->scheduleDelivery($webhook, $event, $data);
            }
            
            Log::info("Scheduled webhook deliveries", [
                'event' => $event,
                'business_id' => $businessId,
                'webhook_count' => $webhooks->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to dispatch webhook event", [
                'event' => $event,
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Schedule webhook delivery
     */
    protected function scheduleDelivery(Webhook $webhook, string $event, array $data): void
    {
        $payload = $this->buildPayload($event, $data, $webhook->business_id);
        
        // Create delivery record
        $delivery = $webhook->deliveries()->create([
            'event_type' => $event,
            'payload' => $payload,
            'status' => WebhookDelivery::STATUS_PENDING,
            'attempt_number' => 1,
            'scheduled_at' => now()
        ]);
        
        // Queue the delivery job
        DeliverWebhook::dispatch($delivery)->onQueue('webhooks');
    }
    
    /**
     * Build webhook payload
     */
    protected function buildPayload(string $event, array $data, int $businessId): array
    {
        return [
            'id' => uniqid('wh_event_'),
            'event' => $event,
            'created_at' => now()->toISOString(),
            'business_id' => $businessId,
            'data' => $data,
            'version' => '1.0'
        ];
    }
    
    /**
     * Deliver webhook immediately (synchronous)
     */
    public function deliverNow(WebhookDelivery $delivery): bool
    {
        $webhook = $delivery->webhook;
        
        try {
            $startTime = microtime(true);
            
            // Prepare headers
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'IsleBooks-Webhooks/1.0',
                'X-Webhook-Event' => $delivery->event_type,
                'X-Webhook-Delivery' => $delivery->id,
                'X-Webhook-Signature' => $this->generateSignature($delivery->payload, $webhook->secret)
            ];
            
            // Make HTTP request
            $response = Http::timeout($webhook->timeout)
                           ->withHeaders($headers)
                           ->post($webhook->url, $delivery->payload);
            
            $responseTime = microtime(true) - $startTime;
            
            // Check if request was successful
            if ($response->successful()) {
                $delivery->markAsSuccess(
                    $response->status(),
                    $response->body(),
                    $responseTime
                );
                
                $webhook->resetFailureCount();
                $webhook->update(['last_triggered_at' => now()]);
                
                Log::info("Webhook delivered successfully", [
                    'webhook_id' => $webhook->id,
                    'delivery_id' => $delivery->id,
                    'event' => $delivery->event_type,
                    'response_time' => $responseTime
                ]);
                
                return true;
                
            } else {
                $this->handleDeliveryFailure(
                    $delivery,
                    "HTTP {$response->status()}: {$response->body()}",
                    $response->status(),
                    $response->body()
                );
                
                return false;
            }
            
        } catch (\Exception $e) {
            $this->handleDeliveryFailure($delivery, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle delivery failure
     */
    protected function handleDeliveryFailure(
        WebhookDelivery $delivery,
        string $errorMessage,
        int $responseStatus = null,
        string $responseBody = null
    ): void {
        $delivery->markAsFailed($errorMessage, $responseStatus, $responseBody);
        $delivery->webhook->incrementFailureCount($responseStatus);
        
        Log::error("Webhook delivery failed", [
            'webhook_id' => $delivery->webhook_id,
            'delivery_id' => $delivery->id,
            'event' => $delivery->event_type,
            'error' => $errorMessage,
            'attempt' => $delivery->attempt_number
        ]);
        
        // Schedule retry if possible
        if ($delivery->canRetry()) {
            $this->scheduleRetry($delivery);
        } else {
            Log::warning("Webhook delivery failed permanently", [
                'webhook_id' => $delivery->webhook_id,
                'delivery_id' => $delivery->id,
                'event' => $delivery->event_type,
                'attempts' => $delivery->attempt_number
            ]);
        }
    }
    
    /**
     * Schedule webhook retry
     */
    protected function scheduleRetry(WebhookDelivery $delivery): void
    {
        $retryDelivery = $delivery->webhook->deliveries()->create([
            'event_type' => $delivery->event_type,
            'payload' => $delivery->payload,
            'status' => WebhookDelivery::STATUS_PENDING,
            'attempt_number' => $delivery->attempt_number + 1,
            'scheduled_at' => $delivery->getNextRetryTime()
        ]);
        
        // Queue retry with delay
        $delay = $delivery->getNextRetryTime();
        DeliverWebhook::dispatch($retryDelivery)->delay($delay)->onQueue('webhooks');
        
        Log::info("Webhook retry scheduled", [
            'original_delivery_id' => $delivery->id,
            'retry_delivery_id' => $retryDelivery->id,
            'scheduled_at' => $delay->toISOString()
        ]);
    }
    
    /**
     * Generate webhook signature
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
        return 'sha256=' . hash_hmac('sha256', $payloadJson, $secret);
    }
    
    /**
     * Test webhook endpoint
     */
    public function testWebhook(Webhook $webhook): array
    {
        $testPayload = [
            'id' => uniqid('wh_test_'),
            'event' => 'webhook.test',
            'created_at' => now()->toISOString(),
            'business_id' => $webhook->business_id,
            'data' => [
                'message' => 'This is a test webhook delivery',
                'webhook_id' => $webhook->id,
                'webhook_name' => $webhook->name
            ],
            'version' => '1.0'
        ];
        
        try {
            $startTime = microtime(true);
            
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'IsleBooks-Webhooks/1.0',
                'X-Webhook-Event' => 'webhook.test',
                'X-Webhook-Test' => 'true',
                'X-Webhook-Signature' => $this->generateSignature($testPayload, $webhook->secret)
            ];
            
            $response = Http::timeout($webhook->timeout)
                           ->withHeaders($headers)
                           ->post($webhook->url, $testPayload);
            
            $responseTime = microtime(true) - $startTime;
            
            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_time' => round($responseTime * 1000, 2), // Convert to milliseconds
                'response_body' => $response->body(),
                'error' => $response->successful() ? null : "HTTP {$response->status()}"
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => null,
                'response_time' => null,
                'response_body' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get webhook statistics
     */
    public function getWebhookStats(Webhook $webhook, int $days = 7): array
    {
        $deliveries = $webhook->deliveries()
            ->where('created_at', '>=', now()->subDays($days))
            ->get();
        
        $total = $deliveries->count();
        $successful = $deliveries->where('status', WebhookDelivery::STATUS_SUCCESS)->count();
        $failed = $deliveries->where('status', WebhookDelivery::STATUS_FAILED)->count();
        $pending = $deliveries->where('status', WebhookDelivery::STATUS_PENDING)->count();
        
        $avgResponseTime = $deliveries->where('status', WebhookDelivery::STATUS_SUCCESS)
                                    ->where('response_time', '>', 0)
                                    ->avg('response_time');
        
        return [
            'period_days' => $days,
            'total_deliveries' => $total,
            'successful_deliveries' => $successful,
            'failed_deliveries' => $failed,
            'pending_deliveries' => $pending,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'avg_response_time' => $avgResponseTime ? round($avgResponseTime * 1000, 2) : 0, // milliseconds
            'health_status' => $webhook->getOverallStatus()
        ];
    }
}