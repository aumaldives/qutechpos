<?php

namespace Modules\Woocommerce\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Woocommerce\Entities\WoocommerceSyncConflict;

class ConflictResolved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WoocommerceSyncConflict $conflict;
    public array $resolutionMetadata;

    /**
     * Create a new event instance.
     *
     * @param WoocommerceSyncConflict $conflict
     * @param array $resolutionMetadata
     */
    public function __construct(WoocommerceSyncConflict $conflict, array $resolutionMetadata = [])
    {
        $this->conflict = $conflict;
        $this->resolutionMetadata = $resolutionMetadata;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel('woocommerce.conflicts.' . $this->conflict->business_id),
            new PrivateChannel('woocommerce.monitoring.' . $this->conflict->business_id)
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'conflict_id' => $this->conflict->id,
            'business_id' => $this->conflict->business_id,
            'entity_type' => $this->conflict->entity_type,
            'entity_id' => $this->conflict->entity_id,
            'woocommerce_id' => $this->conflict->woocommerce_id,
            'field_name' => $this->conflict->field_name,
            'conflict_type' => $this->conflict->conflict_type,
            'severity' => $this->conflict->severity,
            'resolution_strategy' => $this->conflict->resolution_strategy,
            'resolution_notes' => $this->conflict->resolution_notes,
            'resolved_at' => $this->conflict->resolved_at?->toISOString(),
            'resolved_by' => $this->conflict->resolved_by,
            'status' => $this->conflict->status,
            'auto_resolved' => $this->resolutionMetadata['auto_resolved'] ?? false,
            'resolution_method' => $this->resolutionMetadata['resolution_method'] ?? 'manual',
            'processing_time_ms' => $this->resolutionMetadata['processing_time_ms'] ?? null,
            'final_value' => $this->resolutionMetadata['final_value'] ?? null,
            'confidence_score' => $this->resolutionMetadata['confidence_score'] ?? null,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'conflict.resolved';
    }

    /**
     * Determine if this event should be broadcast.
     *
     * @return bool
     */
    public function shouldBroadcast()
    {
        return $this->conflict->business_id && 
               in_array($this->conflict->status, [
                   WoocommerceSyncConflict::STATUS_RESOLVED,
                   WoocommerceSyncConflict::STATUS_AUTO_RESOLVED
               ]);
    }
}