<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Warehouse, User};

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $farmers = User::whereHas('role', fn($r) => $r->where('name', 'farmer'))
            ->get();

        if ($farmers->isEmpty()) {
            $this->command->warn('⚠️ Tidak ditemukan user dengan role farmer. Jalankan UserSeeder dulu.');
            return;
        }

        // Warehouse::truncate();

        foreach ($farmers as $farmer) {
            for ($i = 1; $i <= 2; $i++) {
                Warehouse::create([
                    'farmer_id'  => $farmer->id,
                    'name'       => "Gudang {$i} - {$farmer->name}",
                    'image_url'  => fake()->imageUrl(640, 480, 'warehouse'),
                    'description'=> fake()->sentence(),
                    'location'   => fake()->city(),
                ]);
            }
        }
    }
}
