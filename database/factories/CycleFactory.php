<?php

namespace Database\Factories;

use App\Models\Crop;
use App\Models\Land;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\cycle>
 */
class CycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'land_id' => Land::inRandomOrder()->first()?->id ?? Land::factory(),
            'crop_id' => $Crop = (Crop::inRandomOrder()->first()?->id ?? Crop::factory()),
            'status_id' => Status::inRandomOrder()->first()?->id ?? Status::factory(),
            'name' => 'Cycle ' . Crop::find($Crop)->name,
            'start_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        ];
    }
}
