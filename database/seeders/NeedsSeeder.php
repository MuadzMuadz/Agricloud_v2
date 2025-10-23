<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Needs;

class NeedsSeeder extends Seeder
{
    public function run(): void
    {
        Needs::factory()->count(15)->create();
    }
}
