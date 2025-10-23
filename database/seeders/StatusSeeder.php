<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Pending', 'type' => 'cycle'],
            ['name' => 'Active', 'type' => 'cycle'],
            ['name' => 'Completed', 'type' => 'cycle'],
            ['name' => 'Pending', 'type' => 'phase'],
            ['name' => 'Active', 'type' => 'phase'],
            ['name' => 'Completed', 'type' => 'phase'],
            ['name' => 'In Progress', 'type' => 'movement'],
            ['name' => 'Done', 'type' => 'movement'],
        ];

        Status::insert($statuses);
    }
}
