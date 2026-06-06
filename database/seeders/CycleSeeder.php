<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\Cycle;
use App\Models\Phase;
use App\Models\Stage;
use App\Models\Status;
use Illuminate\Database\Seeder;

class CycleSeeder extends Seeder
{
    public function run(): void
    {
        $crops = Crop::all();

        // Status siklus dideterministikkan (bukan acak via factory) agar fase
        // konsisten dengan status siklus dan selalu ada siklus aktif untuk diuji.
        $cycleStatusFlow = ['Active', 'Completed'];

        foreach ($crops as $crop) {
            foreach ($cycleStatusFlow as $statusName) {
                $cycle = Cycle::factory()->create([
                    'crop_id' => $crop->id,
                    'name' => $crop->name,
                    'status_id' => $this->cycleStatusId($statusName),
                ]);

                $this->seedPhases($cycle, $statusName);
            }
        }
    }

    /**
     * Semai fase koheren untuk satu siklus (stage urut `order`):
     * - Siklus Active  → sebagian Completed, tepat satu Active, sisanya Pending.
     * - Siklus Completed → semua fase Completed (tanpa Active).
     * - Lainnya (Pending) → semua fase Pending.
     */
    private function seedPhases(Cycle $cycle, string $cycleStatusName): void
    {
        $stages = Stage::where('crop_id', $cycle->crop_id)
            ->orderBy('order')
            ->get();

        if ($stages->isEmpty()) {
            return;
        }

        $activeIndex = $cycle->id % $stages->count();

        foreach ($stages->values() as $i => $stage) {
            $state = match ($cycleStatusName) {
                'Active' => match (true) {
                    $i < $activeIndex => 'completed',
                    $i === $activeIndex => 'active',
                    default => 'pending',
                },
                'Completed' => 'completed',
                default => 'pending',
            };

            Phase::factory()->{$state}()->create([
                'cycle_id' => $cycle->id,
                'stage_id' => $stage->id,
            ]);
        }
    }

    /**
     * ID Status `type='cycle'` untuk nama tertentu; dibuat bila belum ada.
     */
    private function cycleStatusId(string $name): int
    {
        return Status::where('type', 'cycle')->where('name', $name)->value('id')
            ?? Status::factory()->create(['name' => $name, 'type' => 'cycle'])->id;
    }
}
