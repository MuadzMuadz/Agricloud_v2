<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Movements, MoveTypes, Warehouse, Land, Items, Status};

class MovementsSeeder extends Seeder
{
    public function run(): void
    {
        $moveTypes = MoveTypes::all();
        $lands = Land::all();
        $warehouses = Warehouse::all();
        $items = Items::all();
        $status = Status::first(); // default status misal "completed" atau "pending"

        if ($items->isEmpty() || $warehouses->isEmpty() || $moveTypes->isEmpty()) {
            $this->command->warn('⚠️ MovementSeeder dilewati (belum ada items / warehouses / move types).');
            return;
        }

        $typeCodes = $moveTypes->pluck('code')->map(fn($code) => strtoupper($code))->toArray();
        $count = 0;

        for ($i = 0; $i < 15; $i++) {
            $typeCode = $typeCodes[$i % 3]; // biar rata: 5 IN, 5 OUT, 5 TRANSFER
            $type = $moveTypes->where('code', $typeCode)->first();
            if (!$type) continue;

            $item = $items->random();
            $sourceWarehouse = $warehouses->random();
            $destinationLand = $lands->random();

            // ambil warehouse lain milik farmer yang sama
            $sameFarmerWarehouses = $warehouses
                ->where('farmer_id', $sourceWarehouse->farmer_id)
                ->where('id', '!=', $sourceWarehouse->id);
            
            if ($typeCode === 'TRANSFER' && $sameFarmerWarehouses->isEmpty()) {
                $typeCode = 'OUT';
                $type = $moveTypes->where('code', 'OUT')->first();
            }

            $destinationWarehouse = $sameFarmerWarehouses->isNotEmpty()
                ? $sameFarmerWarehouses->random()
                : null;

            $movementData = [
                'warehouse_id'   => null,
                'item_id'        => $item->id,
                'movetype_id'    => $type->id,
                'status_id'      => $status?->id ?? 1, // default status
                'land_dest'      => null,
                'warehouse_dest' => null,
                'quantity'       => rand(1, 30),
                'note'           => null,
            ];

            switch ($typeCode) {
                case 'IN':
                    // Barang masuk ke gudang (hanya warehouse_dest)
                    $movementData['warehouse_dest'] = $sourceWarehouse->id;
                    break;

                case 'OUT':
                    // Barang keluar dari gudang ke lahan
                    $movementData['warehouse_id'] = $sourceWarehouse->id;
                    $movementData['land_dest'] = $destinationLand->id;
                    break;

                case 'TRANSFER':
                    // Barang pindah antar gudang milik farmer yang sama
                    if ($destinationWarehouse) {
                        $movementData['warehouse_id'] = $sourceWarehouse->id;
                        $movementData['warehouse_dest'] = $destinationWarehouse->id;
                    }
                    break;
            }

            // insert kalau minimal satu arah valid
            if (
                $movementData['warehouse_id'] ||
                $movementData['warehouse_dest'] ||
                $movementData['land_dest']
            ) {
                Movements::create($movementData);
                $count++;
            }
        }

        $this->command->info("✅ MovementSeeder: $count movement berhasil dibuat (pakai struktur kolom terbaru).");
    }
}
