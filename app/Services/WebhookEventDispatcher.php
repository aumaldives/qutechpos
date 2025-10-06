<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Contact;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class WebhookEventDispatcher
{
    protected WebhookService $webhookService;
    
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }
    
    /**
     * Dispatch product events
     */
    public function dispatchProductEvent(string $action, Product $product, array $changes = []): void
    {
        $eventType = "product.{$action}";
        
        $data = [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'type' => $product->type,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'unit_id' => $product->unit_id,
                'is_active' => $product->enable_stock,
                'created_at' => $product->created_at?->toISOString(),
                'updated_at' => $product->updated_at?->toISOString()
            ]
        ];
        
        // Add stock information for stock-related events
        if (in_array($action, ['stock_updated', 'created', 'updated'])) {
            $data['product']['current_stock'] = $product->current_stock ?? 0;
            $data['product']['alert_quantity'] = $product->alert_quantity ?? 0;
        }
        
        // Add change information for update events
        if ($action === 'updated' && !empty($changes)) {
            $data['changes'] = $changes;
        }
        
        $this->webhookService->dispatch($eventType, $data, $product->business_id);
    }
    
    /**
     * Dispatch contact events
     */
    public function dispatchContactEvent(string $action, Contact $contact, array $changes = []): void
    {
        $eventType = "contact.{$action}";
        
        $data = [
            'contact' => [
                'id' => $contact->id,
                'type' => $contact->type,
                'name' => $contact->name,
                'email' => $contact->email ?? null,
                'mobile' => $contact->mobile,
                'city' => $contact->city,
                'state' => $contact->state,
                'country' => $contact->country,
                'is_default' => $contact->is_default,
                'created_at' => $contact->created_at?->toISOString(),
                'updated_at' => $contact->updated_at?->toISOString()
            ]
        ];
        
        // Add change information for update events
        if ($action === 'updated' && !empty($changes)) {
            $data['changes'] = $changes;
        }
        
        $this->webhookService->dispatch($eventType, $data, $contact->business_id);
    }
    
    /**
     * Dispatch transaction events
     */
    public function dispatchTransactionEvent(string $action, Transaction $transaction, array $additionalData = []): void
    {
        $eventType = "transaction.{$action}";
        
        $data = [
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'status' => $transaction->status,
                'payment_status' => $transaction->payment_status,
                'contact_id' => $transaction->contact_id,
                'business_location_id' => $transaction->location_id,
                'invoice_no' => $transaction->invoice_no,
                'transaction_date' => $transaction->transaction_date,
                'final_total' => (float) $transaction->final_total,
                'tax_amount' => (float) ($transaction->tax_amount ?? 0),
                'discount_amount' => (float) ($transaction->discount_amount ?? 0),
                'shipping_charges' => (float) ($transaction->shipping_charges ?? 0),
                'additional_notes' => $transaction->additional_notes,
                'created_at' => $transaction->created_at?->toISOString(),
                'updated_at' => $transaction->updated_at?->toISOString()
            ]
        ];
        
        // Add contact information if available
        if ($transaction->contact) {
            $data['contact'] = [
                'id' => $transaction->contact->id,
                'name' => $transaction->contact->name,
                'type' => $transaction->contact->type,
                'mobile' => $transaction->contact->mobile
            ];
        }
        
        // Add transaction lines for creation events
        if (in_array($action, ['created', 'updated']) && $transaction->sell_lines) {
            $data['transaction']['lines'] = $transaction->sell_lines->map(function ($line) {
                return [
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price_before_discount,
                    'unit_price_inc_tax' => (float) $line->unit_price_inc_tax,
                    'line_discount_amount' => (float) ($line->line_discount_amount ?? 0)
                ];
            })->toArray();
        }
        
        // Add any additional data
        if (!empty($additionalData)) {
            $data = array_merge($data, $additionalData);
        }
        
        $this->webhookService->dispatch($eventType, $data, $transaction->business_id);
    }
    
    /**
     * Dispatch sale-specific events
     */
    public function dispatchSaleEvent(string $action, Transaction $sale): void
    {
        if ($sale->type !== 'sell') {
            return; // Only process sales
        }
        
        $eventType = "sale.{$action}";
        
        $data = [
            'sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'status' => $sale->status,
                'payment_status' => $sale->payment_status,
                'contact_id' => $sale->contact_id,
                'business_location_id' => $sale->location_id,
                'transaction_date' => $sale->transaction_date,
                'final_total' => (float) $sale->final_total,
                'total_before_tax' => (float) $sale->total_before_tax,
                'tax_amount' => (float) ($sale->tax_amount ?? 0),
                'created_at' => $sale->created_at?->toISOString()
            ]
        ];
        
        // Add customer information
        if ($sale->contact) {
            $data['customer'] = [
                'id' => $sale->contact->id,
                'name' => $sale->contact->name,
                'mobile' => $sale->contact->mobile,
                'email' => $sale->contact->email
            ];
        }
        
        // Add payment information for completed sales
        if (in_array($action, ['completed', 'created']) && $sale->payment_lines) {
            $data['payments'] = $sale->payment_lines->map(function ($payment) {
                return [
                    'method' => $payment->method,
                    'amount' => (float) $payment->amount,
                    'paid_on' => $payment->paid_on
                ];
            })->toArray();
        }
        
        $this->webhookService->dispatch($eventType, $data, $sale->business_id);
    }
    
    /**
     * Dispatch purchase-specific events
     */
    public function dispatchPurchaseEvent(string $action, Transaction $purchase): void
    {
        if ($purchase->type !== 'purchase') {
            return; // Only process purchases
        }
        
        $eventType = "purchase.{$action}";
        
        $data = [
            'purchase' => [
                'id' => $purchase->id,
                'ref_no' => $purchase->ref_no,
                'status' => $purchase->status,
                'payment_status' => $purchase->payment_status,
                'contact_id' => $purchase->contact_id,
                'business_location_id' => $purchase->location_id,
                'transaction_date' => $purchase->transaction_date,
                'final_total' => (float) $purchase->final_total,
                'total_before_tax' => (float) $purchase->total_before_tax,
                'tax_amount' => (float) ($purchase->tax_amount ?? 0),
                'created_at' => $purchase->created_at?->toISOString()
            ]
        ];
        
        // Add supplier information
        if ($purchase->contact) {
            $data['supplier'] = [
                'id' => $purchase->contact->id,
                'name' => $purchase->contact->name,
                'supplier_business_name' => $purchase->contact->supplier_business_name,
                'mobile' => $purchase->contact->mobile
            ];
        }
        
        $this->webhookService->dispatch($eventType, $data, $purchase->business_id);
    }
    
    /**
     * Dispatch payment events
     */
    public function dispatchPaymentEvent(string $action, $payment, Transaction $transaction): void
    {
        $eventType = match ($action) {
            'added' => 'transaction.payment_added',
            'updated' => 'transaction.payment_updated',
            default => "payment.{$action}"
        };
        
        $data = [
            'payment' => [
                'id' => $payment->id ?? null,
                'method' => $payment->method,
                'amount' => (float) $payment->amount,
                'paid_on' => $payment->paid_on,
                'note' => $payment->note ?? null
            ],
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'invoice_no' => $transaction->invoice_no ?? $transaction->ref_no,
                'final_total' => (float) $transaction->final_total,
                'payment_status' => $transaction->payment_status
            ]
        ];
        
        $this->webhookService->dispatch($eventType, $data, $transaction->business_id);
    }
    
    /**
     * Dispatch stock events
     */
    public function dispatchStockEvent(string $action, array $stockData, int $businessId): void
    {
        $eventType = "stock.{$action}";
        
        $data = [
            'stock' => $stockData,
            'timestamp' => now()->toISOString()
        ];
        
        $this->webhookService->dispatch($eventType, $data, $businessId);
    }
    
    /**
     * Dispatch business events
     */
    public function dispatchBusinessEvent(string $action, array $businessData, int $businessId): void
    {
        $eventType = "business.{$action}";
        
        $data = [
            'business' => $businessData,
            'timestamp' => now()->toISOString()
        ];
        
        $this->webhookService->dispatch($eventType, $data, $businessId);
    }
    
    /**
     * Generic method to dispatch any custom event
     */
    public function dispatchCustomEvent(string $eventType, array $data, int $businessId): void
    {
        $this->webhookService->dispatch($eventType, $data, $businessId);
    }
}