<?php

namespace App\Services;

use App\Models\{Cycle, Crop, Phase, Status};
use Illuminate\Support\Carbon;

class CycleService
{
    /**
     * Buat cycle baru + auto-generate phases berdasarkan crop template
     */
    public function createCycleWithPhases(array $data, $user): Cycle
    {
        // 🔹 pastikan lahan milik user login
        if (! $user->lands()->where('id', $data['land_id'])->exists()) {
            abort(403, 'You do not own this land.');
        }

        // 🔹 Generate timeline cycle + status
        [$start, $end, $cycleStatusId] = $this->generateCycleTimeline($data);

        $crop = Crop::with('stages')->findOrFail($data['crop_id']);

        // 🔹 nama cycle = nama crop
        $cycle = Cycle::create([
            'land_id' => $data['land_id'],
            'crop_id' => $crop->id,
            'name' => $crop->name,
            'description' => $crop->description,
            'status_id' => $cycleStatusId,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        // 🔹 generate phases dari crop.stages
        $this->generatePhasesFromStages($cycle, $crop, $start);

        // 🔹 load relasi biar langsung lengkap
        return $cycle->load('phases.stage', 'phases.status');
    }

    private function generateCycleTimeline(array $data): array
    {
        $start = isset($data['start_date']) && $data['start_date']
            ? Carbon::parse($data['start_date'])
            : Carbon::now();

        $crop = Crop::with('stages')->find($data['crop_id']);
        if (! $crop) {
            abort(404, 'Crop not found.');
        }

        $totalDays = $crop->stages->sum(fn($s) => $s->duration_days ?? $s->days ?? 0);
        $end = $start->copy()->addDays($totalDays);

        $now = Carbon::now();
        $statusName =
            $now->lt($start) ? 'Pending' :
            ($now->gt($end) ? 'Completed' : 'Active');

        $statusId = Status::where('type', 'cycle')
            ->whereRaw('LOWER(name) = ?', [strtolower($statusName)])
            ->value('id');

        if (! $statusId) {
            abort(500, "Status '{$statusName}' for type 'cycle' not found.");
        }

        return [$start->toDateTimeString(), $end->toDateTimeString(), $statusId];
    }

    /**
     * 🔹 Generate phases sesuai urutan stage + durasi hari
     */
    private function generatePhasesFromStages(Cycle $cycle, Crop $crop, $cycleStart): void
    {
        $currentStart = Carbon::parse($cycleStart);

        foreach ($crop->stages as $stage) {
            $duration = $stage->duration_days ?? $stage->days ?? 0;

            $phaseStart = $currentStart->copy();
            $phaseEnd = $phaseStart->copy()->addDays($duration);

            // tentuin status phase
            $now = Carbon::now();
            $statusName =
                $now->lt($phaseStart) ? 'Pending' :
                ($now->gt($phaseEnd) ? 'Completed' : 'Active');

            $statusId = Status::where('type', 'phase')
                ->whereRaw('LOWER(name) = ?', [strtolower($statusName)])
                ->value('id');

            if (! $statusId) {
                abort(500, "Status '{$statusName}' for type 'phase' not found.");
            }

            // simpan phase
            Phase::create([
                'cycle_id'   => $cycle->id,
                'stage_id'   => $stage->id,
                'name'       => $stage->name,
                'status_id'  => $statusId,
                'started_at' => $phaseStart->toDateTimeString(),
                'ended_at'   => $phaseEnd->toDateTimeString(),
            ]);

            // lanjut ke fase berikutnya
            $currentStart = $phaseEnd;
        }
    }
}
