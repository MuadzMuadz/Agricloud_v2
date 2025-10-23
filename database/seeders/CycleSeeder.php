<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Cycle, Phase};

class CycleSeeder extends Seeder
{
    public function run(): void
    {
        $crops = \App\Models\Crop::all();

        foreach ($crops as $crop) {
            Cycle::factory()
                ->count(2)
                ->has(Phase::factory()->count(3))
                ->create([
                    'crop_id' => $crop->id,
                    'name' => $crop->name,
                ]);
        }
    }
}
