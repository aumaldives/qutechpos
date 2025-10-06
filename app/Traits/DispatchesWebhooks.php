<?php

namespace App\Traits;

use App\Services\WebhookEventDispatcher;

trait DispatchesWebhooks
{
    /**
     * Get webhook event dispatcher instance
     */
    protected function getWebhookDispatcher(): WebhookEventDispatcher
    {
        return app(WebhookEventDispatcher::class);
    }
    
    /**
     * Dispatch product stock update event
     */
    protected function dispatchStockUpdateEvent(int $productId, array $stockChanges, int $businessId): void
    {
        $dispatcher = $this->getWebhookDispatcher();
        
        $stockData = [
            'product_id' => $productId,
            'changes' => $stockChanges,
            'updated_at' => now()->toISOString()
        ];
        
        $dispatcher->dispatchStockEvent('stock_updated', $stockData, $businessId);
    }
    
    /**
     * Dispatch low stock alert event
     */
    protected function dispatchLowStockAlert(array $products, int $businessId): void
    {
        $dispatcher = $this->getWebhookDispatcher();
        
        $stockData = [
            'products' => $products,
            'alert_type' => 'low_stock',
            'triggered_at' => now()->toISOString()
        ];
        
        $dispatcher->dispatchStockEvent('low_alert', $stockData, $businessId);
    }
    
    /**
     * Dispatch payment added event
     */
    protected function dispatchPaymentAddedEvent($payment, \App\Transaction $transaction): void
    {
        $dispatcher = $this->getWebhookDispatcher();
        $dispatcher->dispatchPaymentEvent('added', $payment, $transaction);
    }
    
    /**
     * Dispatch custom webhook event
     */
    protected function dispatchWebhookEvent(string $eventType, array $data, int $businessId): void
    {
        $dispatcher = $this->getWebhookDispatcher();
        $dispatcher->dispatchCustomEvent($eventType, $data, $businessId);
    }
    
    /**
     * Dispatch transaction status change event
     */
    protected function dispatchTransactionStatusChange(
        \App\Transaction $transaction, 
        string $oldStatus, 
        string $newStatus
    ): void {
        $dispatcher = $this->getWebhookDispatcher();
        
        $additionalData = [
            'status_change' => [
                'from' => $oldStatus,
                'to' => $newStatus,
                'changed_at' => now()->toISOString()
            ]
        ];
        
        $dispatcher->dispatchTransactionEvent('status_changed', $transaction, $additionalData);
    }
    
    /**
     * Dispatch refund event
     */
    protected function dispatchRefundEvent(\App\Transaction $sale, array $refundData): void
    {
        if ($sale->type !== 'sell') {
            return;
        }
        
        $dispatcher = $this->getWebhookDispatcher();
        
        $eventData = [
            'refund' => $refundData,
            'original_sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'final_total' => (float) $sale->final_total
            ]
        ];
        
        $dispatcher->dispatchCustomEvent('sale.refunded', $eventData, $sale->business_id);
    }
}