<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\movetypes>
 */
class MovetypesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Barang Masuk', 'Barang Keluar', 'Transfer']),
            'code' => $this->faker->unique()->randomElement(['IN', 'OUT', 'TRANSFER']),
        ];
    }
}
