<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Menjalankan seluruh DatabaseSeeder (jalur integrasi data nyata).
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_runs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, DB::table('roles')->count(), 'roles kosong setelah seeding');
        $this->assertGreaterThan(0, DB::table('users')->count(), 'users kosong setelah seeding');
        $this->assertGreaterThan(0, DB::table('lands')->count(), 'lands kosong setelah seeding');
        $this->assertGreaterThan(0, DB::table('cycles')->count(), 'cycles kosong setelah seeding');
        $this->assertGreaterThan(0, DB::table('movements')->count(), 'movements kosong setelah seeding');
    }
}
