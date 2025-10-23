<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\land>
 */
class LandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
        {
            return [
                'farmer_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
                'name' => 'Lahan ' . $this->faker->word(),
                'latitude' => $this->faker->latitude(-6.2, -6.3),
                'longitude' => $this->faker->longitude(106.7, 106.9),
                'image_url' => $this->faker->imageUrl(640, 480, 'farm'),
                'area' => $this->faker->randomFloat(2, 0.1, 5),
            ];
        }

}
