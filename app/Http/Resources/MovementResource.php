<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Memetakan baris `movements` ke kontrak FE riwayat transaksi gudang
     * (lihat Plan Movements-Backend §2 — IInventoryRow):
     * - `date`      -> `created_at`.
     * - `item_name` -> nama relasi `Item`.
     * - `category`  -> nama relasi `Item.Category`.
     * - `qty`       -> kolom `quantity`.
     * - `direction` -> arah dari `Movetype.code` (IN->Masuk, OUT->Keluar, TRANSFER->Transfer).
     * - `status`    -> nama relasi `Status`.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->created_at,
            'item_id' => $this->item_id,
            'item_name' => $this->Item?->name,
            'category' => $this->Item?->Category?->name,
            'qty' => (float) $this->quantity,
            'direction' => self::mapDirection($this->Movetype?->code),
            'note' => $this->note,
            'status' => $this->Status?->name,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Petakan kode Movetype ke arah transaksi (kontrak FE).
     */
    public static function mapDirection(?string $code): ?string
    {
        return match ($code) {
            'IN' => 'Masuk',
            'OUT' => 'Keluar',
            'TRANSFER' => 'Transfer',
            default => $code,
        };
    }
}
