<?php

namespace Database\Seeders;

use App\Models\Needs;
use Illuminate\Database\Seeder;

class NeedsSeeder extends Seeder
{
    public function run(): void
    {
        Needs::factory()->count(15)->create();
    }
}
