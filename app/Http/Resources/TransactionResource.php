<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type, // purchase, sell, expense, stock_adjustment, etc.
            'status' => $this->status, // received, pending, ordered, draft, final, etc.
            'ref_no' => $this->ref_no,
            'invoice_no' => $this->invoice_no,
            'transaction_date' => $this->transaction_date,
            
            // Contact information
            'contact' => $this->whenLoaded('contact', function () {
                return [
                    'id' => $this->contact->id,
                    'name' => $this->contact->name,
                    'contact_id' => $this->contact->contact_id,
                    'type' => $this->contact->type,
                    'mobile' => $this->contact->mobile,
                    'email' => $this->contact->email,
                ];
            }),
            
            // Location information
            'location' => $this->whenLoaded('location', function () {
                return [
                    'id' => $this->location->id,
                    'name' => $this->location->name,
                    'landmark' => $this->location->landmark,
                ];
            }),
            
            // Financial summary
            'totals' => [
                'total_before_tax' => $this->total_before_tax ? (float) $this->total_before_tax : 0,
                'tax_amount' => $this->tax_amount ? (float) $this->tax_amount : 0,
                'shipping_charges' => $this->shipping_charges ? (float) $this->shipping_charges : 0,
                'additional_notes' => $this->additional_notes,
                'round_off_amount' => $this->round_off_amount ? (float) $this->round_off_amount : 0,
                'final_total' => $this->final_total ? (float) $this->final_total : 0,
                'discount_amount' => $this->discount_amount ? (float) $this->discount_amount : 0,
                'discount_type' => $this->discount_type, // fixed, percentage
            ],
            
            // Payment information
            'payment_info' => [
                'payment_status' => $this->payment_status, // paid, partial, due
                'total_paid' => $this->payment_lines_sum_amount ? (float) $this->payment_lines_sum_amount : 0,
                'balance_due' => $this->final_total ? (float) ($this->final_total - ($this->payment_lines_sum_amount ?? 0)) : 0,
                'payment_terms' => $this->pay_term_number && $this->pay_term_type ? 
                    $this->pay_term_number . ' ' . $this->pay_term_type : null,
            ],
            
            // Tax information
            'tax_info' => [
                'tax_id' => $this->tax_id,
                'tax_percent' => $this->tax_id ? (float) $this->tax_percent : null,
                'is_tax_inclusive' => (bool) $this->is_tax_inclusive,
            ],
            
            // Staff and tracking
            'staff_info' => [
                'created_by' => $this->created_by,
                'sales_person' => $this->whenLoaded('sales_person', function () {
                    return [
                        'id' => $this->sales_person->id,
                        'name' => $this->sales_person->first_name . ' ' . $this->sales_person->last_name,
                    ];
                }),
                'commission_agent' => $this->commission_agent,
            ],
            
            // Additional details
            'details' => [
                'invoice_scheme_id' => $this->invoice_scheme_id,
                'is_direct_sale' => (bool) $this->is_direct_sale,
                'is_quotation' => (bool) $this->is_quotation,
                'delivery_date' => $this->delivery_date,
                'staff_note' => $this->staff_note,
                'shipping_details' => $this->shipping_details,
                'packing_charge' => $this->packing_charge ? (float) $this->packing_charge : null,
                'packing_charge_type' => $this->packing_charge_type,
            ],
            
            // Status tracking
            'status_info' => [
                'is_suspend' => (bool) $this->is_suspend,
                'sub_status' => $this->sub_status,
                'delivery_status' => $this->delivery_status,
                'is_created_from_api' => (bool) $this->is_created_from_api,
            ],
            
            // Related records
            'related_transactions' => [
                'return_parent_id' => $this->return_parent_id,
                'sales_order_ids' => $this->sales_order_ids,
                'purchase_order_ids' => $this->purchase_order_ids,
            ],
            
            // Include transaction lines when requested
            'lines' => $this->when(
                $request->boolean('include_lines'),
                function () {
                    if ($this->type === 'sell') {
                        return $this->sell_lines->map(function ($line) {
                            return [
                                'id' => $line->id,
                                'product_id' => $line->product_id,
                                'variation_id' => $line->variation_id,
                                'product_name' => $line->product->name ?? null,
                                'product_sku' => $line->variation->sub_sku ?? null,
                                'quantity' => (float) $line->quantity,
                                'unit_price' => (float) $line->unit_price,
                                'unit_price_inc_tax' => (float) $line->unit_price_inc_tax,
                                'line_discount_type' => $line->line_discount_type,
                                'line_discount_amount' => (float) $line->line_discount_amount,
                                'item_tax' => (float) $line->item_tax,
                            ];
                        });
                    } elseif ($this->type === 'purchase') {
                        return $this->purchase_lines->map(function ($line) {
                            return [
                                'id' => $line->id,
                                'product_id' => $line->product_id,
                                'variation_id' => $line->variation_id,
                                'product_name' => $line->product->name ?? null,
                                'product_sku' => $line->variation->sub_sku ?? null,
                                'quantity' => (float) $line->quantity,
                                'purchase_price' => (float) $line->purchase_price,
                                'purchase_price_inc_tax' => (float) $line->purchase_price_inc_tax,
                                'item_tax' => (float) $line->item_tax,
                            ];
                        });
                    }
                    return [];
                }
            ),
            
            // Include payments when requested
            'payments' => $this->when(
                $request->boolean('include_payments'),
                function () {
                    return $this->payment_lines->map(function ($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => (float) $payment->amount,
                            'method' => $payment->method,
                            'paid_on' => $payment->paid_on,
                            'account_id' => $payment->account_id,
                            'cheque_number' => $payment->cheque_number,
                            'card_type' => $payment->card_type,
                            'card_number' => $payment->card_number,
                            'card_transaction_number' => $payment->card_transaction_number,
                            'note' => $payment->note,
                            'created_at' => $payment->created_at?->toISOString(),
                        ];
                    });
                }
            ),
            
            // Timestamps
            'dates' => [
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
            
            // Business context
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
        ];
    }
}