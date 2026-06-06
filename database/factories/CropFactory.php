<?php

namespace Database\Factories;

use App\Models\crop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<crop>
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
            'category' => $this->faker->randomElement(['Sayuran Daun', 'Buah', 'Pangan', 'Umbi', 'Hortikultura']),
            'image_url' => $this->faker->imageUrl(),
        ];
    }
}
