<?php

namespace App\Observers;

use App\Services\WebhookEventDispatcher;
use Illuminate\Database\Eloquent\Model;

class WebhookObserver
{
    protected WebhookEventDispatcher $dispatcher;
    
    public function __construct(WebhookEventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->handleModelEvent('created', $model);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        $changes = $this->getRelevantChanges($model);
        if (!empty($changes)) {
            $this->handleModelEvent('updated', $model, $changes);
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->handleModelEvent('deleted', $model);
    }
    
    /**
     * Handle model events based on model type
     */
    protected function handleModelEvent(string $action, Model $model, array $changes = []): void
    {
        try {
            switch (get_class($model)) {
                case \App\Models\Product::class:
                    $this->dispatcher->dispatchProductEvent($action, $model, $changes);
                    break;
                    
                case \App\Contact::class:
                    $this->dispatcher->dispatchContactEvent($action, $model, $changes);
                    break;
                    
                case \App\Transaction::class:
                    $this->handleTransactionEvent($action, $model, $changes);
                    break;
                    
                default:
                    // Skip unknown model types
                    break;
            }
        } catch (\Exception $e) {
            // Log error but don't break the main operation
            \Log::error('Webhook observer failed', [
                'model' => get_class($model),
                'action' => $action,
                'model_id' => $model->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle transaction-specific events
     */
    protected function handleTransactionEvent(string $action, \App\Transaction $transaction, array $changes = []): void
    {
        // Dispatch generic transaction event
        $this->dispatcher->dispatchTransactionEvent($action, $transaction);
        
        // Dispatch type-specific events for sales and purchases
        if ($transaction->type === 'sell') {
            $this->dispatcher->dispatchSaleEvent($action, $transaction);
            
            // Dispatch specific sale status events
            if ($action === 'updated' && isset($changes['status'])) {
                $newStatus = $changes['status']['new'];
                if ($newStatus === 'final') {
                    $this->dispatcher->dispatchSaleEvent('completed', $transaction);
                } elseif ($newStatus === 'cancelled') {
                    $this->dispatcher->dispatchSaleEvent('cancelled', $transaction);
                }
            }
            
            // Dispatch payment status change events
            if ($action === 'updated' && isset($changes['payment_status'])) {
                $this->dispatcher->dispatchTransactionEvent('payment_status_changed', $transaction, [
                    'old_payment_status' => $changes['payment_status']['old'],
                    'new_payment_status' => $changes['payment_status']['new']
                ]);
            }
            
        } elseif ($transaction->type === 'purchase') {
            $this->dispatcher->dispatchPurchaseEvent($action, $transaction);
            
            // Dispatch specific purchase status events
            if ($action === 'updated' && isset($changes['status'])) {
                $newStatus = $changes['status']['new'];
                if ($newStatus === 'received') {
                    $this->dispatcher->dispatchPurchaseEvent('received', $transaction);
                } elseif ($newStatus === 'cancelled') {
                    $this->dispatcher->dispatchPurchaseEvent('cancelled', $transaction);
                }
            }
        }
    }
    
    /**
     * Get relevant changes for webhook events
     */
    protected function getRelevantChanges(Model $model): array
    {
        $dirty = $model->getDirty();
        $original = $model->getOriginal();
        
        // Remove timestamps and irrelevant fields
        $ignoredFields = [
            'updated_at',
            'created_at',
            'deleted_at',
            'remember_token'
        ];
        
        $changes = [];
        
        foreach ($dirty as $field => $newValue) {
            if (in_array($field, $ignoredFields)) {
                continue;
            }
            
            $oldValue = $original[$field] ?? null;
            
            // Only include if value actually changed
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        return $changes;
    }
}