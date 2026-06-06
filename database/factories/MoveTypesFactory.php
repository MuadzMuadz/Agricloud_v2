<?php

namespace Database\Factories;

use App\Models\MoveTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoveTypes>
 */
class MoveTypesFactory extends Factory
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
