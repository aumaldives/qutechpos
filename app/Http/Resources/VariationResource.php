<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VariationResource extends JsonResource
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
            'sub_sku' => $this->sub_sku,
            'variation_value_id' => $this->variation_value_id,
            'default_purchase_price' => (float) $this->default_purchase_price,
            'dpp_inc_tax' => (float) $this->dpp_inc_tax,
            'profit_percent' => (float) $this->profit_percent,
            'default_sell_price' => (float) $this->default_sell_price,
            'sell_price_inc_tax' => (float) $this->sell_price_inc_tax,
            'combo_variations' => $this->combo_variations,
            'variation_template' => [
                'id' => $this->whenLoaded('variation_template', $this->variation_template?->id),
                'name' => $this->whenLoaded('variation_template', $this->variation_template?->name),
            ],
            'variation_value' => [
                'id' => $this->variation_value_id,
                'name' => $this->whenLoaded('variation_value', $this->variation_value?->name),
            ],
            'media' => $this->whenLoaded('media', function () {
                return $this->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'file_name' => $media->file_name,
                        'mime_type' => $media->mime_type,
                        'url' => $media->getUrl(),
                        'thumbnail_url' => $media->getUrl('thumb'),
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
