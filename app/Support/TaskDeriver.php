<?php

namespace App\Support;

use App\Models\Cycle;
use App\Models\Items;
use App\Models\Needs;
use App\Models\User;
use Carbon\Carbon;

/**
 * Menurunkan "Tindakan Hari Ini" (computed, tanpa tabel `tasks`) dari domain
 * milik user — arah A-lite pada Plan [[Dashboard-TasksAlerts-Backend]].
 *
 * Tiga sumber tindakan (semua owner-scoped lewat farmer_id):
 *  1. harvest  — `cycles` aktif dengan `end_date` <= hari ini + N hari.
 *  2. input    — `needs` pada fase aktif yang belum terpenuhi (stok item < butuh).
 *  3. restock  — `items` dengan `stock` < config('agricloud.low_stock_threshold').
 *
 * Tiap item berbentuk:
 *  { id (sintetis), title, field_id, field_name, type, due_date, urgency }
 * dengan urgency ∈ {today, due, urgent, upcoming} dihitung dari selisih tanggal.
 */
class TaskDeriver
{
    /**
     * Kumpulkan seluruh tindakan milik user, terurut berdasarkan prioritas urgency.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forUser(User $user): array
    {
        $tasks = array_merge(
            self::harvestTasks($user),
            self::inputTasks($user),
            self::restockTasks($user),
        );

        // Urutkan: yang paling butuh perhatian di atas (due > urgent > today > upcoming).
        usort($tasks, fn ($a, $b) => self::urgencyRank($a['urgency']) <=> self::urgencyRank($b['urgency']));

        return $tasks;
    }

    /**
     * KPI "Perlu Perhatian" = jumlah tindakan ber-urgency ∈ {due, urgent}.
     */
    public static function attentionCount(array $tasks): int
    {
        return count(array_filter(
            $tasks,
            fn ($task) => in_array($task['urgency'], ['due', 'urgent'], true)
        ));
    }

    /**
     * (1) Panen mendekat: siklus aktif milik user dengan end_date dalam jangkauan.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function harvestTasks(User $user): array
    {
        $lookahead = (int) config('agricloud.harvest_lookahead_days', 7);
        $limit = Carbon::today()->addDays($lookahead)->toDateString();

        $cycles = Cycle::whereHas('Land', fn ($q) => $q->where('farmer_id', $user->id))
            ->whereHas('Status', fn ($s) => $s->where('name', 'Active')->where('type', 'cycle'))
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', $limit)
            ->with(['Land', 'Crop'])
            ->get();

        return $cycles->map(function (Cycle $cycle) {
            $cropName = $cycle->Crop?->name;
            $fieldName = $cycle->Land?->name;
            if ($fieldName !== null && $cropName !== null) {
                $fieldName .= ' — '.$cropName;
            }

            return [
                'id' => 'cycle-'.$cycle->id.'-harvest',
                'title' => $cropName !== null ? 'Estimasi panen '.$cropName : 'Estimasi panen',
                'field_id' => $cycle->land_id,
                'field_name' => $fieldName,
                'type' => 'harvest',
                'due_date' => Carbon::parse($cycle->end_date)->toDateString(),
                'urgency' => self::urgencyFromDate($cycle->end_date),
            ];
        })->all();
    }

    /**
     * (2) Kebutuhan input belum terpenuhi pada fase aktif (stok item < quantity_needed).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function inputTasks(User $user): array
    {
        $needs = Needs::whereHas('Phase', function ($p) use ($user) {
            $p->whereHas('Status', fn ($s) => $s->where('name', 'Active')->where('type', 'phase'))
                ->whereHas('Cycle.Land', fn ($l) => $l->where('farmer_id', $user->id));
        })
            ->with(['Item', 'Phase.Cycle.Land'])
            ->get()
            // Belum terpenuhi: item hilang, atau stok kurang dari yang dibutuhkan.
            ->filter(fn (Needs $need) => $need->Item === null || $need->Item->stock < $need->quantity_needed);

        return $needs->map(function (Needs $need) {
            $land = $need->Phase?->Cycle?->Land;
            $itemName = $need->Item?->name;

            return [
                'id' => 'need-'.$need->id.'-input',
                'title' => $itemName !== null ? 'Pemberian input: '.$itemName : 'Pemberian input',
                'field_id' => $land?->id,
                'field_name' => $land?->name,
                'type' => 'input',
                'due_date' => null,
                // Tanpa tanggal terjadwal → dianggap perlu perhatian (jatuh tempo).
                'urgency' => 'due',
            ];
        })->all();
    }

    /**
     * (3) Stok menipis: item milik user dengan stock < low_stock_threshold.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function restockTasks(User $user): array
    {
        $threshold = (int) config('agricloud.low_stock_threshold', 10);

        $items = Items::whereHas('Warehouse', fn ($q) => $q->where('farmer_id', $user->id))
            ->where('stock', '<', $threshold)
            ->with('Warehouse')
            ->get();

        return $items->map(fn (Items $item) => [
            'id' => 'item-'.$item->id.'-restock',
            'title' => 'Stok menipis: '.$item->name,
            // Restock terkait gudang, bukan lahan → tak ada field.
            'field_id' => null,
            'field_name' => null,
            'type' => 'restock',
            'due_date' => null,
            'urgency' => 'urgent',
        ])->all();
    }

    /**
     * Hitung urgency dari selisih hari (tanggal) antara hari ini & due_date.
     *  - lewat (negatif) → due
     *  - hari ini (0)    → today
     *  - <= ambang urgent → urgent
     *  - selebihnya       → upcoming
     */
    public static function urgencyFromDate($date): string
    {
        $urgentWindow = (int) config('agricloud.urgent_window_days', 2);

        $days = (int) Carbon::today()->diffInDays(Carbon::parse($date)->startOfDay(), false);

        return match (true) {
            $days < 0 => 'due',
            $days === 0 => 'today',
            $days <= $urgentWindow => 'urgent',
            default => 'upcoming',
        };
    }

    /**
     * Bobot pengurutan: makin kecil makin mendesak (untuk usort).
     */
    private static function urgencyRank(string $urgency): int
    {
        return match ($urgency) {
            'due' => 0,
            'urgent' => 1,
            'today' => 2,
            'upcoming' => 3,
            default => 4,
        };
    }
}
