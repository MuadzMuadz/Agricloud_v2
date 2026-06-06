<?php

namespace App\Http\Resources;

use App\Models\Cycle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CycleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Memetakan kolom DB siklus tanam ke kontrak FE (lihat Plan Cycle-Backend §4):
     * - `plant_name`             -> nama tanaman dari relasi `Crop`.
     * - `name`                   -> nama siklus custom yang diketik user (kolom `name`).
     * - `status`                 -> nama Status dipetakan (Active->active, Pending->pending, Completed->done).
     * - `phase`                  -> nama Stage dari fase aktif (`phases` ber-Status Active type phase), null bila tak ada.
     * - `progress`               -> diturunkan dari rentang tanggal start/end, null bila salah satu kosong.
     * - `estimated_harvest_date` -> kolom `end_date`.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plant_name' => $this->Crop?->name,
            'name' => $this->name,
            'field_id' => $this->land_id,
            'start_date' => $this->start_date,
            'status' => self::mapStatus($this->Status?->name),
            'phase' => self::activePhaseName($this->resource),
            'progress' => self::progressFromDates($this->start_date, $this->end_date),
            'estimated_harvest_date' => $this->end_date,
        ];
    }

    /**
     * Petakan nama Status DB ke nilai kontrak FE.
     */
    public static function mapStatus(?string $name): ?string
    {
        return match ($name) {
            'Active' => 'active',
            'Pending' => 'pending',
            'Completed' => 'done',
            default => $name !== null ? strtolower($name) : null,
        };
    }

    /**
     * Nama Stage dari fase aktif siklus (relasi `Phases` harus sudah dimuat).
     * Fase aktif = Phase dengan Status {name: Active, type: phase}.
     */
    public static function activePhaseName(Cycle $cycle): ?string
    {
        if (! $cycle->relationLoaded('Phases')) {
            return null;
        }

        $active = $cycle->Phases->first(
            fn ($phase) => optional($phase->Status)->name === 'Active'
                && optional($phase->Status)->type === 'phase'
        );

        return $active?->Stage?->name;
    }

    /**
     * Progress 0–100 diturunkan dari posisi hari ini di rentang start..end.
     * Null bila salah satu tanggal kosong atau rentang tidak valid.
     */
    public static function progressFromDates($start, $end): ?int
    {
        if (empty($start) || empty($end)) {
            return null;
        }

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $total = $start->diffInSeconds($end, false);
        if ($total <= 0) {
            return null;
        }

        $elapsed = $start->diffInSeconds(Carbon::now(), false);
        $ratio = max(0.0, min(1.0, $elapsed / $total));

        return (int) round($ratio * 100);
    }
}
