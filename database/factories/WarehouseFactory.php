<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        $farmerId = User::whereHas('role', fn ($r) => $r->where('name', 'farmer'))
            ->inRandomOrder()
            ->value('id');

        return [
            'farmer_id' => $farmerId,
            'name' => 'Gudang '.ucfirst($this->faker->word()),
            'location' => $this->faker->city(),
            'image_url' => $this->faker->imageUrl(640, 480, 'warehouse', true),
        ];
    }
}
