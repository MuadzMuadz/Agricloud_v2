<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Crop, Stage};

class StageSeeder extends Seeder
{
    public function run(): void
    {
        // pastikan sudah ada crop
        if (Crop::count() === 0) {
            $crops = \App\Models\Crop::factory()->count(4)->create();
        } else {
            $crops = Crop::all();
        }

        foreach ($crops as $crop) {
            $baseStages = [
                ['name' => 'Penyemaian', 'order' => 1, 'duration_days' => 7],
                ['name' => 'Pertumbuhan', 'order' => 2, 'duration_days' => 14],
                ['name' => 'Panen', 'order' => 3, 'duration_days' => 5],
            ];

            // kalau crop Tomat → tambahkan panen ke-n
            if (strtolower($crop->name) === 'tomat') {
                $extraPanen = rand(3, 10); // berapa kali panen tambahan
                for ($i = 2; $i <= $extraPanen; $i++) {
                    $baseStages[] = [
                        'name' => "Panen ke-$i",
                        'order' => 3 + ($i - 1),
                        'duration_days' => 5,
                    ];
                }
            }

            foreach ($baseStages as $stage) {
                Stage::create([
                    'crop_id'       => $crop->id,
                    'name'          => $stage['name'],
                    'order'         => $stage['order'],
                    'duration_days' => $stage['duration_days'],
                ]);
            }
        }
    }
}
