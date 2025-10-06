<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
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
            'type' => $this->type, // customer, supplier, both
            'name' => $this->name,
            'contact_id' => $this->contact_id, // Custom contact ID/code
            'business_name' => $this->supplier_business_name,
            'contact_info' => [
                'mobile' => $this->mobile,
                'landline' => $this->landline,
                'alternate_number' => $this->alternate_number,
                'email' => $this->email,
            ],
            'address' => [
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'zip_code' => $this->zip_code,
            ],
            'shipping_address' => [
                'shipping_address' => $this->shipping_address,
            ],
            'financial' => [
                'credit_limit' => $this->credit_limit ? (float) $this->credit_limit : null,
                'pay_term_number' => $this->pay_term_number,
                'pay_term_type' => $this->pay_term_type,
                'opening_balance' => $this->total_rp ? (float) $this->total_rp : 0,
            ],
            'tax_info' => [
                'tax_number' => $this->tax_number,
                'contact_status' => $this->contact_status,
            ],
            'dates' => [
                'dob' => $this->dob,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
            'settings' => [
                'is_default' => (bool) $this->is_default,
                'is_active' => (bool) $this->is_active,
                'enable_portal' => (bool) $this->enable_portal,
            ],
            'custom_fields' => [
                'custom_field1' => $this->custom_field1,
                'custom_field2' => $this->custom_field2,
                'custom_field3' => $this->custom_field3,
                'custom_field4' => $this->custom_field4,
            ],
            
            // Include business-related data when requested
            'business_id' => $this->business_id,
            'created_by' => $this->created_by,
            
            // Include transaction summary when requested
            'transaction_summary' => $this->when(
                $request->boolean('include_transactions'),
                function () {
                    return [
                        'total_purchase' => $this->total_purchase ?? 0,
                        'total_purchase_paid' => $this->total_purchase_paid ?? 0,
                        'total_purchase_due' => ($this->total_purchase ?? 0) - ($this->total_purchase_paid ?? 0),
                        'total_sell' => $this->total_sell ?? 0,
                        'total_sell_paid' => $this->total_sell_paid ?? 0,
                        'total_sell_due' => ($this->total_sell ?? 0) - ($this->total_sell_paid ?? 0),
                        'total_sell_return' => $this->total_sell_return ?? 0,
                        'total_purchase_return' => $this->total_purchase_return ?? 0,
                    ];
                }
            ),
            
            // Include customer group when available
            'customer_group' => $this->whenLoaded('customer_group', function () {
                return [
                    'id' => $this->customer_group->id,
                    'name' => $this->customer_group->name,
                    'percentage' => (float) $this->customer_group->percentage,
                ];
            }),
        ];
    }
}
