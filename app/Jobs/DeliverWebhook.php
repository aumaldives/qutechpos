<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected WebhookDelivery $delivery;
    
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1; // We handle retries manually
    
    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(WebhookDelivery $delivery)
    {
        $this->delivery = $delivery;
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        try {
            // Check if delivery is still valid
            if (!$this->delivery->exists || $this->delivery->status !== WebhookDelivery::STATUS_PENDING) {
                Log::warning('Webhook delivery job skipped - delivery no longer pending', [
                    'delivery_id' => $this->delivery->id,
                    'status' => $this->delivery->status
                ]);
                return;
            }
            
            // Check if webhook is still active
            if (!$this->delivery->webhook || !$this->delivery->webhook->is_active) {
                $this->delivery->update(['status' => WebhookDelivery::STATUS_CANCELLED]);
                Log::info('Webhook delivery cancelled - webhook inactive', [
                    'delivery_id' => $this->delivery->id,
                    'webhook_id' => $this->delivery->webhook_id
                ]);
                return;
            }
            
            Log::info('Processing webhook delivery', [
                'delivery_id' => $this->delivery->id,
                'webhook_id' => $this->delivery->webhook_id,
                'event' => $this->delivery->event_type,
                'attempt' => $this->delivery->attempt_number
            ]);
            
            // Attempt delivery
            $success = $webhookService->deliverNow($this->delivery);
            
            if ($success) {
                Log::info('Webhook delivery completed successfully', [
                    'delivery_id' => $this->delivery->id,
                    'webhook_id' => $this->delivery->webhook_id,
                    'event' => $this->delivery->event_type
                ]);
            } else {
                Log::warning('Webhook delivery failed', [
                    'delivery_id' => $this->delivery->id,
                    'webhook_id' => $this->delivery->webhook_id,
                    'event' => $this->delivery->event_type,
                    'attempt' => $this->delivery->attempt_number
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Webhook delivery job failed with exception', [
                'delivery_id' => $this->delivery->id,
                'webhook_id' => $this->delivery->webhook_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Mark delivery as failed
            $this->delivery->markAsFailed("Job exception: " . $e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook delivery job failed permanently', [
            'delivery_id' => $this->delivery->id,
            'webhook_id' => $this->delivery->webhook_id,
            'error' => $exception->getMessage()
        ]);
        
        $this->delivery->markAsFailed("Job failed: " . $exception->getMessage());
    }
    
    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'webhook',
            'delivery:' . $this->delivery->id,
            'webhook:' . $this->delivery->webhook_id,
            'event:' . $this->delivery->event_type
        ];
    }
}