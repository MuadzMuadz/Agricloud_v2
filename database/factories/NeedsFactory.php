<?php

namespace Database\Factories;

use App\Models\Items;
use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\needs>
 */
class NeedsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phase_id' => Phase::inRandomOrder()->first()?->id ?? Phase::factory(),
            'item_id' => Items::inRandomOrder()->first()?->id ?? Items::factory(),
            'quantity_needed' => $this->faker->numberBetween(1, 10),
        ];
    }
}
