<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil id role berdasarkan nama (jangan hardcode id — rapuh terhadap sequence DB)
        $adminRoleId = Role::where('name', 'admin')->value('id');
        $farmerRoleId = Role::where('name', 'farmer')->value('id');

        // Buat 1 admin dan beberapa farmer
        User::factory()->create([
            'name' => 'Admin AgriCloud',
            'email' => 'admin@agricloud.test',
            'password' => bcrypt('adminpassword'),
            'role_id' => $adminRoleId,
        ]);

        User::factory()->count(5)->create([
            'role_id' => $farmerRoleId,
        ]);
    }
}
