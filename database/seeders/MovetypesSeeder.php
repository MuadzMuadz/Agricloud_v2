<?php

namespace Database\Seeders;

use App\Models\MoveTypes;
use Illuminate\Database\Seeder;

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
