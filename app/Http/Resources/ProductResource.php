<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode_type' => $this->barcode_type,
            'type' => $this->type,
            'unit' => [
                'id' => $this->unit_id,
                'name' => $this->whenLoaded('unit', $this->unit?->actual_name),
                'short_name' => $this->whenLoaded('unit', $this->unit?->short_name),
            ],
            'category' => [
                'id' => $this->category_id,
                'name' => $this->whenLoaded('category', $this->category?->name),
                'parent_id' => $this->whenLoaded('category', $this->category?->parent_id),
            ],
            'sub_category' => [
                'id' => $this->sub_category_id,
                'name' => $this->whenLoaded('sub_category', $this->sub_category?->name),
            ],
            'brand' => [
                'id' => $this->brand_id,
                'name' => $this->whenLoaded('brand', $this->brand?->name),
            ],
            'business_id' => $this->business_id,
            'tax' => $this->tax,
            'tax_type' => $this->tax_type,
            'enable_stock' => (bool) $this->enable_stock,
            'alert_quantity' => $this->alert_quantity,
            'is_inactive' => (bool) $this->is_inactive,
            'not_for_selling' => (bool) $this->not_for_selling,
            'image' => $this->image ? asset('uploads/img/' . $this->image) : null,
            'description' => $this->product_description,
            'weight' => $this->weight,
            'warranty' => [
                'id' => $this->warranty_id,
                'name' => $this->whenLoaded('warranty', $this->warranty?->name),
                'duration' => $this->whenLoaded('warranty', $this->warranty?->duration),
                'duration_type' => $this->whenLoaded('warranty', $this->warranty?->duration_type),
            ],
            'variations' => VariationResource::collection($this->whenLoaded('variations')),
            'modifier_sets' => ModifierSetResource::collection($this->whenLoaded('modifier_sets')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Include business location stock if requested
            'stock' => $this->when(
                $request->has('include_stock'), 
                function () use ($request) {
                    $location_id = $request->get('location_id');
                    if ($location_id && $this->relationLoaded('variations')) {
                        return $this->variations->map(function ($variation) use ($location_id) {
                            $locationDetail = $variation->variation_location_details
                                ->where('location_id', $location_id)
                                ->first();
                            
                            return [
                                'variation_id' => $variation->id,
                                'location_id' => $location_id,
                                'quantity' => $locationDetail?->qty_available ?? 0,
                                'selling_price' => $locationDetail?->default_sell_price ?? 0,
                                'purchase_price' => $locationDetail?->default_purchase_price ?? 0,
                            ];
                        });
                    }
                    return null;
                }
            ),
            
            // Include additional data when specifically requested
            'profit_margins' => $this->when(
                $request->has('include_margins'),
                function () {
                    return $this->variations->map(function ($variation) {
                        return [
                            'variation_id' => $variation->id,
                            'profit_percent' => $variation->profit_percent,
                            'selling_price' => $variation->sell_price_inc_tax,
                            'cost_price' => $variation->default_purchase_price,
                            'margin_amount' => $variation->sell_price_inc_tax - $variation->default_purchase_price,
                        ];
                    });
                }
            ),
        ];
    }
}
