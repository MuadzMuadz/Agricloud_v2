<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Crop, Stage};

class CropSeeder extends Seeder
{
    public function run(): void
    {
        // buat 4 template crop
        $crops = [
            ['name' => 'Bayam', 'image_url' => 'https://picsum.photos/200?random=1'],
            ['name' => 'Kangkung', 'image_url' => 'https://picsum.photos/200?random=2'],
            ['name' => 'Tomat', 'image_url' => 'https://picsum.photos/200?random=3'],
            ['name' => 'Selada', 'image_url' => 'https://picsum.photos/200?random=4'],
        ];

        foreach ($crops as $cropData) {
            $crop = Crop::create($cropData);

            // tahap dasar
            $baseStages = [
                ['name' => 'Penyemaian', 'order' => 1, 'duration_days' => 7],
                ['name' => 'Pertumbuhan', 'order' => 2, 'duration_days' => 14],
                ['name' => 'Panen', 'order' => 3, 'duration_days' => 5],
            ];

            // kalau tomat → multiple harvest (Panen ke-n)
            if (strtolower($crop->name) === 'tomat') {
                $extraPanen = rand(3, 10);
                for ($i = 2; $i <= $extraPanen; $i++) {
                    $baseStages[] = [
                        'name' => "Panen ke-$i",
                        'order' => 3 + ($i - 1),
                        'duration_days' => 5,
                    ];
                }
            }

            // simpan semua stage untuk crop ini
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
