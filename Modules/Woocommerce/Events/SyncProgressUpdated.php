<?php

namespace Modules\Woocommerce\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $progressData;

    /**
     * Create a new event instance.
     *
     * @param array $progressData
     */
    public function __construct(array $progressData)
    {
        $this->progressData = $progressData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('woocommerce.sync.' . $this->progressData['business_id']);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'business_id' => $this->progressData['business_id'],
            'sync_type' => $this->progressData['sync_type'] ?? null,
            'stage' => $this->progressData['stage'] ?? null,
            'status' => $this->progressData['status'] ?? null,
            'percentage' => $this->progressData['percentage'] ?? null,
            'total_items' => $this->progressData['total_items'] ?? null,
            'processed_items' => $this->progressData['processed_items'] ?? null,
            'successful_items' => $this->progressData['successful_items'] ?? null,
            'failed_items' => $this->progressData['failed_items'] ?? null,
            'conflicts_created' => $this->progressData['conflicts_created'] ?? null,
            'conflicts_resolved' => $this->progressData['conflicts_resolved'] ?? null,
            'current_operation' => $this->progressData['current_operation'] ?? null,
            'estimated_completion' => $this->progressData['estimated_completion'] ?? null,
            'result' => $this->progressData['result'] ?? null,
            'error' => $this->progressData['error'] ?? null,
            'errors' => $this->progressData['errors'] ?? [],
            'data' => $this->progressData['data'] ?? [],
            'timestamp' => $this->progressData['timestamp'] ?? now()->toISOString(),
            'job_id' => $this->progressData['job_id'] ?? null
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'sync.progress.updated';
    }
}