<?php

namespace Modules\Woocommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Business;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use Modules\Woocommerce\Services\WebhookProcessorService;
use Exception;

class WooCommerceWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes
    public $tries = 3;
    public $backoff = [30, 90, 300]; // 30sec, 1.5min, 5min
    public $maxExceptions = 3;

    protected string $business_id;
    protected string $event;
    protected array $payload;
    protected array $metadata;

    public function __construct(
        string $business_id,
        string $event,
        array $payload,
        array $metadata = []
    ) {
        $this->business_id = $business_id;
        $this->event = $event;
        $this->payload = $payload;
        $this->metadata = $metadata;
        
        $this->onQueue('woocommerce-webhooks');
    }

    /**
     * Execute the webhook processing job
     */
    public function handle(WebhookProcessorService $processor): void
    {
        $webhookId = $this->metadata['webhook_id'] ?? 'unknown';
        
        Log::info('Processing webhook job', [
            'webhook_id' => $webhookId,
            'business_id' => $this->business_id,
            'event' => $this->event,
            'payload_id' => $this->payload['id'] ?? 'unknown',
            'job_attempt' => $this->attempts()
        ]);

        DB::beginTransaction();

        try {
            // Validate business still exists
            $business = Business::find($this->business_id);
            if (!$business) {
                throw new Exception("Business {$this->business_id} not found");
            }

            // Process the webhook based on event type
            $result = $processor->processWebhook(
                $this->business_id,
                $this->event,
                $this->payload,
                $this->metadata
            );

            DB::commit();

            Log::info('Webhook processed successfully', [
                'webhook_id' => $webhookId,
                'business_id' => $this->business_id,
                'event' => $this->event,
                'result' => $result,
                'processing_attempt' => $this->attempts()
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Webhook processing failed', [
                'webhook_id' => $webhookId,
                'business_id' => $this->business_id,
                'event' => $this->event,
                'payload_id' => $this->payload['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure after all retries
     */
    public function failed(Exception $exception): void
    {
        $webhookId = $this->metadata['webhook_id'] ?? 'unknown';

        Log::error('Webhook job permanently failed', [
            'webhook_id' => $webhookId,
            'business_id' => $this->business_id,
            'event' => $this->event,
            'payload_id' => $this->payload['id'] ?? 'unknown',
            'final_error' => $exception->getMessage(),
            'total_attempts' => $this->attempts(),
            'failed_at' => now()->toISOString()
        ]);

        // Optionally notify administrators about permanent webhook failures
        $this->notifyAdministrators($exception);
    }

    /**
     * Determine retry delay
     */
    public function retryAfter(): int
    {
        $attempt = $this->attempts();
        return $this->backoff[$attempt - 1] ?? 300; // Default to 5 minutes
    }

    /**
     * Notify administrators about permanent webhook failures
     */
    protected function notifyAdministrators(Exception $exception): void
    {
        try {
            // This could send an email, Slack notification, etc.
            // For now, we'll just log it with a special marker for monitoring
            Log::critical('WEBHOOK_PERMANENT_FAILURE', [
                'business_id' => $this->business_id,
                'event' => $this->event,
                'payload_id' => $this->payload['id'] ?? 'unknown',
                'error' => $exception->getMessage(),
                'webhook_id' => $this->metadata['webhook_id'] ?? 'unknown',
                'requires_manual_intervention' => true
            ]);
            
        } catch (Exception $e) {
            // Don't fail if notification fails
            Log::error('Failed to notify administrators about webhook failure', [
                'notification_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get job display name for monitoring
     */
    public function displayName(): string
    {
        $webhookId = $this->metadata['webhook_id'] ?? 'unknown';
        $payloadId = $this->payload['id'] ?? 'unknown';
        
        return "WooCommerce Webhook: {$this->event} (Business: {$this->business_id}, ID: {$payloadId}, Webhook: {$webhookId})";
    }
}