<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Items, Warehouse, Categories};

class ItemsSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $categories = Categories::all();

        if ($warehouses->isEmpty()) {
            $this->command->warn('⚠️ Tidak ada warehouse. Jalankan WarehouseSeeder dulu.');
            return;
        }

        if ($categories->isEmpty()) {
            $this->command->warn('⚠️ Tidak ada kategori. Jalankan CategorySeeder dulu.');
            return;
        }

        // Items::truncate();

        foreach ($warehouses as $warehouse) {
            for ($i = 1; $i <= 5; $i++) {
                Items::create([
                    'warehouse_id' => $warehouse->id,
                    'category_id'  => $categories->random()->id,
                    'name'         => "Item {$i} - {$warehouse->name}",
                    'stock'        => fake()->numberBetween(10, 200),
                    'unit'         => fake()->randomElement(['kg', 'liter', 'pcs']),
                ]);
            }
        }

        $this->command->info('✅ ItemSeeder: 10 item per warehouse berhasil dibuat.');
    }
}
