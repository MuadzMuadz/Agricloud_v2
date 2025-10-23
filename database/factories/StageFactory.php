<?php

namespace Database\Factories;

use App\Models\Stage;
use App\Models\Crop;
use Illuminate\Database\Eloquent\Factories\Factory;

class StageFactory extends Factory
{
    protected $model = Stage::class;

    public function definition(): array
    {
        // Ambil crop acak atau buat baru kalau belum ada
        $crop = Crop::inRandomOrder()->first() ?? Crop::factory()->create();

        // Default single stage (Factory tetap sederhana)
        return [
            'crop_id' => $crop->id,
            'name' => $this->faker->randomElement(['Penyemaian', 'Pertumbuhan', 'Panen']),
            'order' => $this->faker->numberBetween(1, 3),
            'duration_days' => $this->faker->numberBetween(5, 20),
        ];
    }
}
