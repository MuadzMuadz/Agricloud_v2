<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat 1 admin dan beberapa farmer
        User::factory()->create([
            'name' => 'Admin AgriCloud',
            'email' => 'admin@agricloud.test',
            'password' => bcrypt('adminpassword'),
            'role_id' => 1,
        ]);
        User::factory()->create([
            'name' => 'ryvn',
            'email' => 'ryvn@agricloud.test',
            'password' => bcrypt('password'),
            'role_id' => 2,
        ]);

        User::factory()->count(5)->create([
            'role_id' => 2,
        ]);
    }
}
