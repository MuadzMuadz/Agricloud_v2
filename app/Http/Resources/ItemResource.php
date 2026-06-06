<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Memetakan kolom DB ke kontrak FE (item / FormItemModal):
     * - `category` -> nama kategori (relasi `Category`), null bila belum diisi.
     * - `category_id` & `warehouse_id` diekspos langsung untuk kebutuhan form.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,
            'stock' => $this->stock,
            'category' => $this->whenLoaded('Category', fn () => $this->Category?->name, $this->Category?->name),
            'category_id' => $this->category_id,
            'warehouse_id' => $this->warehouse_id,
            'created_at' => $this->created_at,
        ];
    }
}
