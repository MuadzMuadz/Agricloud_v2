<?php

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\Stage;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\phase>
 */
class PhaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cycle_id' => ($cycle = Cycle::inRandomOrder()->first() ?: Cycle::factory()->create())->id,
            'stage_id' => ($stage = Stage::where('crop_id', $cycle->crop_id)->inRandomOrder()->first() ?: Stage::factory()->create(['crop_id' => $cycle->crop_id]))->id,
            'status_id' => (Status::inRandomOrder()->first() ?: Status::factory()->create())->id,
            'started_at' => $this->faker->dateTimeBetween('-10 days', 'now'),
            'ended_at' => $this->faker->dateTimeBetween('now', '+10 days'),
        ];
    }
}
