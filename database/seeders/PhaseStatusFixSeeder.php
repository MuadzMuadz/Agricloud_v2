<?php

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\Phase;
use App\Models\Stage;
use App\Models\Status;
use Illuminate\Database\Seeder;

/**
 * Perbaikan data fase eksisting (tiket [[Cycle-PhaseStatusSeed]]).
 *
 * Sebelum perbaikan, `PhaseFactory` lama mengisi `status_id` acak lintas type,
 * sehingga mayoritas baris `phases` nyangkut ke Status `type='movement'` dan
 * `GET /api/cycles` hampir selalu mengembalikan `phase: null`.
 *
 * Seeder ini **remap** (bukan hapus — `needs.phase_id` cascadeOnDelete, jadi
 * menghapus phase akan ikut menghapus kebutuhan/needs) `status_id` tiap phase ke
 * padanan `type='phase'`, menjamin **tepat satu** fase `phase/Active` untuk tiap
 * siklus yang berstatus Active. Idempoten: dijalankan berulang menghasilkan
 * keadaan yang sama.
 */
class PhaseStatusFixSeeder extends Seeder
{
    public function run(): void
    {
        $pending = $this->phaseStatusId('Pending');
        $active = $this->phaseStatusId('Active');
        $completed = $this->phaseStatusId('Completed');

        $cycles = Cycle::with(['Status', 'Phases.Stage'])->get();

        foreach ($cycles as $cycle) {
            $this->fixCycle($cycle, $pending, $active, $completed);
        }
    }

    /**
     * Perbaiki fase satu siklus. Siklus Active → completed* + tepat satu active +
     * pending* (urut stage `order`). Siklus non-Active → semua fase Completed
     * (siklus selesai) atau Pending — tanpa fase Active.
     */
    private function fixCycle(Cycle $cycle, int $pending, int $active, int $completed): void
    {
        $isActive = optional($cycle->Status)->name === 'Active';

        $phases = $cycle->Phases
            ->sort(function (Phase $a, Phase $b) {
                $orderA = optional($a->Stage)->order ?? PHP_INT_MAX;
                $orderB = optional($b->Stage)->order ?? PHP_INT_MAX;

                return [$orderA, $a->id] <=> [$orderB, $b->id];
            })
            ->values();

        // Siklus aktif tanpa fase sama sekali → semai dari stages agar phase terisi.
        if ($phases->isEmpty()) {
            if ($isActive) {
                $this->seedMissingPhases($cycle, $pending, $active, $completed);
            }

            return;
        }

        if (! $isActive) {
            $target = optional($cycle->Status)->name === 'Completed' ? $completed : $pending;
            foreach ($phases as $phase) {
                $this->applyStatus($phase, $target);
            }

            return;
        }

        $activeIndex = $cycle->id % $phases->count();

        foreach ($phases as $i => $phase) {
            match (true) {
                $i < $activeIndex => $this->applyStatus($phase, $completed, now()->subDays(20), now()->subDays(10)),
                $i === $activeIndex => $this->applyStatus($phase, $active, now()->subDays(5), null),
                default => $this->applyStatus($phase, $pending, null, null),
            };
        }
    }

    /**
     * Set status (dan opsional tanggal) bila berubah — hemat write & idempoten.
     */
    private function applyStatus(Phase $phase, int $statusId, $startedAt = false, $endedAt = false): void
    {
        $dirty = false;

        if ($phase->status_id !== $statusId) {
            $phase->status_id = $statusId;
            $dirty = true;
        }

        if ($startedAt !== false) {
            $phase->started_at = $startedAt;
            $dirty = true;
        }

        if ($endedAt !== false) {
            $phase->ended_at = $endedAt;
            $dirty = true;
        }

        if ($dirty) {
            $phase->save();
        }
    }

    /**
     * Buat fase koheren untuk siklus aktif yang belum punya fase, urut stage.
     */
    private function seedMissingPhases(Cycle $cycle, int $pending, int $active, int $completed): void
    {
        $stages = Stage::where('crop_id', $cycle->crop_id)->orderBy('order')->get();

        if ($stages->isEmpty()) {
            return;
        }

        $activeIndex = $cycle->id % $stages->count();

        foreach ($stages->values() as $i => $stage) {
            [$statusId, $startedAt, $endedAt] = match (true) {
                $i < $activeIndex => [$completed, now()->subDays(20), now()->subDays(10)],
                $i === $activeIndex => [$active, now()->subDays(5), null],
                default => [$pending, null, null],
            };

            Phase::create([
                'cycle_id' => $cycle->id,
                'stage_id' => $stage->id,
                'status_id' => $statusId,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
            ]);
        }
    }

    /**
     * ID Status `type='phase'` untuk nama tertentu; dibuat bila belum ada.
     */
    private function phaseStatusId(string $name): int
    {
        return Status::where('type', 'phase')->where('name', $name)->value('id')
            ?? Status::factory()->create(['name' => $name, 'type' => 'phase'])->id;
    }
}
