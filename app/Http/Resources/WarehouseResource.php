<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'farmer' => [
                'id' => $this->farmer->id,
                'username' => $this->farmer->username,
                'name' => $this->farmer->name,
            ],
            'name' => $this->name,
            'image_url' => $this->image_url
                ? url($this->image_url)
                : asset('images/default-warehouse.png'),
            'description' => $this->description,
            'location' => $this->location,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),

            'items_count' => $this->whenLoaded('items', fn() => $this->items->count()),
            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
            ])),
        ];
    }
}
