<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Land;

class LandSeeder extends Seeder
{
    public function run(): void
    {
        Land::factory()->count(5)->create();
    }
}
