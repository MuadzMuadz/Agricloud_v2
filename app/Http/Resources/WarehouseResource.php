<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
                : Storage::url('images/default-warehouse.jpeg'),
            'description' => $this->description,
            'location' => $this->location,
            'created_at' => formatDate($this->created_at),
            'updated_at' => formatDate($this->updated_at),

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
