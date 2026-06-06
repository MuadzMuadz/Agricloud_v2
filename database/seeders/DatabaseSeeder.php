<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            LandSeeder::class,
            CropSeeder::class,
            // StageSeeder::class,
            // StatusSeeder harus sebelum CycleSeeder agar fase nyambung ke
            // Status type='phase' (lihat [[Cycle-PhaseStatusSeed]]).
            StatusSeeder::class,
            CycleSeeder::class,
            PhaseSeeder::class,
            WarehouseSeeder::class,
            CategoriesSeeder::class,
            ItemsSeeder::class,
            MovetypesSeeder::class,
            MovementsSeeder::class,
            NeedsSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
