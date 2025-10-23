<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\crop>
 */
class CropFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['Tomat', 'Selada', 'Bayam', 'Kangkung']),
            'description' => $this->faker->paragraph(),
            'image_url' => $this->faker->imageUrl(),
        ];
    }
}
