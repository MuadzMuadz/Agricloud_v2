<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Memetakan kolom DB ke kontrak FE web:
     * - `thumbnail` -> kolom `image_url`.
     * - `location`  -> objek dari kolom `latitude` & `longitude`.
     * - `owner`     -> ringkasan relasi `Farmer`.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'thumbnail' => $this->image_url ? url($this->image_url) : null,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'area' => $this->area,
            'boundary' => $this->boundary, // array [[lat,lng], ...] berkat cast model
            'crops' => $this->whenLoaded('Cycles', fn () => $this->Cycles
                ->map(fn ($c) => ['id' => $c->Crop?->id, 'name' => $c->Crop?->name])
                ->filter(fn ($crop) => $crop['id'] !== null)
                ->unique('id')
                ->values()
            ),
            // Ringkasan satu siklus aktif (yang paling baru bila tumpang sari); null bila tak ada.
            // Daftar lengkap tanaman tetap di `crops[]`. Relasi `Cycles` harus sudah di-eager-load.
            'active_cycle' => $this->whenLoaded('Cycles', function () {
                $active = $this->Cycles->sortByDesc('start_date')->first();

                if (! $active) {
                    return null;
                }

                return [
                    'plant_name' => $active->Crop?->name,
                    'phase' => CycleResource::activePhaseName($active),
                    'progress' => CycleResource::progressFromDates($active->start_date, $active->end_date),
                ];
            }),
            'owner' => [
                'id' => $this->farmer_id,
                'name' => $this->whenLoaded('Farmer', fn () => $this->Farmer->name, $this->Farmer?->name),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
