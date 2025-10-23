<?php

namespace Database\Seeders;

use App\Models\Categories;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        Categories::insert([
            ['name' => 'Bibit'],
            ['name' => 'Pupuk'],
            ['name' => 'Alat'],
            ['name' => 'Obat'],
        ]);
    }
}
