<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MoveTypes;

class MoveTypesSeeder extends Seeder
{
    public function run(): void
    {
        MoveTypes::insert([
            ['name' => 'Barang Masuk', 'code' => 'IN'],
            ['name' => 'Barang Keluar', 'code' => 'OUT'],
            ['name' => 'Transfer', 'code' => 'TRANSFER'],
        ]);
    }
}
