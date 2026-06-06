<?php

namespace Database\Factories;

use App\Models\Items;
use App\Models\Land;
use App\Models\movements;
use App\Models\MoveTypes;
use App\Models\Status;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<movements>
 */
class MovementsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::inRandomOrder()->first()?->id ?? Warehouse::factory(),
            'item_id' => Items::inRandomOrder()->first()?->id ?? Items::factory(),
            'movetype_id' => MoveTypes::inRandomOrder()->first()?->id ?? MoveTypes::factory(),
            'status_id' => Status::inRandomOrder()->first()?->id ?? Status::factory(),
            'land_dest' => ($isLand = $this->faker->boolean())
                ? (Land::inRandomOrder()->first()?->id ?? Land::factory())
                : null,
            'warehouse_dest' => ! $isLand
                ? (Warehouse::inRandomOrder()->first()?->id ?? Warehouse::factory())
                : null,
            'quantity' => $this->faker->numberBetween(1, 20),
            'note' => $this->faker->sentence(),
        ];
    }
}
