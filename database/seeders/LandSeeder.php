<?php

namespace Database\Seeders;

use App\Models\Land;
use Illuminate\Database\Seeder;

class LandSeeder extends Seeder
{
    public function run(): void
    {
        Land::factory()->count(5)->create();
    }
}
