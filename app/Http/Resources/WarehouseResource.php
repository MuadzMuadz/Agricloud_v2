<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Memetakan kolom DB ke kontrak FE web (IWarehouse):
     * - `thumbnail`   -> kolom `image_url`.
     * - `address`     -> kolom `location` (string alamat).
     * - `items_count` -> jumlah jenis item (withCount('Items')).
     * - `stock_total` -> total stok seluruh item (withSum('Items', 'stock')),
     *                    dipakai FE untuk menghitung kapasitas terpakai di list.
     * - `used`        -> alias kapasitas terpakai = SUM(items.stock); FE hitung
     *                    `usage% = used / capacity * 100` (widget "hampir penuh").
     * - `items`       -> daftar item (ItemResource) saat relasi di-load (show).
     * - `location`    -> objek dari kolom `latitude` & `longitude`.
     * - `owner`       -> ringkasan relasi `Farmer`, konsisten dgn LandResource.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'thumbnail' => $this->image_url ? url($this->image_url) : null,
            'address' => $this->location,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'items_count' => $this->whenCounted('Items'),
            'stock_total' => (int) ($this->items_sum_stock ?? 0),
            'used' => (int) ($this->items_sum_stock ?? 0),
            'items' => ItemResource::collection($this->whenLoaded('Items')),
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'owner' => [
                'id' => $this->farmer_id,
                'name' => $this->whenLoaded('Farmer', fn () => $this->Farmer->name, $this->Farmer?->name),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
