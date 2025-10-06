<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModifierSetResource extends JsonResource
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
            'type' => $this->type,
            'modifiers' => $this->whenLoaded('modifiers', function () {
                return $this->modifiers->map(function ($modifier) {
                    return [
                        'id' => $modifier->id,
                        'name' => $modifier->name,
                        'price' => (float) $modifier->price,
                        'is_required' => (bool) $modifier->is_required,
                        'sort_order' => $modifier->sort_order,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
