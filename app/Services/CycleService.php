<?php
namespace App\Services;

use App\Models\{Cycle, Phase, Crop};

class CycleService
{
    public function createCycleWithPhases(array $data, $user)
    {
        $crop = Crop::with('stages')->findOrFail($data['crop_id']);

        $cycle = Cycle::create([
            'land_id' => $data['land_id'],
            'crop_id' => $crop->id,
            'name' => $crop->name,
            'status' => 'preparing',
            'user_id' => $user->id ?? null, // optional kalau cycle tracking di land
        ]);

        foreach ($crop->stages as $stage) {
            Phase::create([
                'cycle_id' => $cycle->id,
                'name' => $stage->name,
                'order' => $stage->order,
                'duration_days' => $stage->duration_days,
                'status' => 'pending',
            ]);
        }

        return $cycle;
    }
}
