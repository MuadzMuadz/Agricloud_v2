<?php

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\Phase;
use App\Models\Stage;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Phase>
 */
class PhaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Fase selalu nyambung ke Status ber-`type='phase'` (lihat tiket
     * [[Cycle-PhaseStatusSeed]]) — bukan acak lintas type. `stage_id` diambil
     * dari stage milik crop siklus, bukan acak global. Default state = Pending.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cycle = Cycle::inRandomOrder()->first() ?: Cycle::factory()->create();
        $stage = Stage::where('crop_id', $cycle->crop_id)->inRandomOrder()->first()
            ?: Stage::factory()->create(['crop_id' => $cycle->crop_id]);

        return [
            'cycle_id' => $cycle->id,
            'stage_id' => $stage->id,
            'status_id' => self::phaseStatusId('Pending'),
            'started_at' => null,
            'ended_at' => null,
        ];
    }

    /**
     * Fase berjalan: Status phase/Active, sudah mulai, belum berakhir.
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'status_id' => self::phaseStatusId('Active'),
            'started_at' => now()->subDays(5),
            'ended_at' => null,
        ]);
    }

    /**
     * Fase selesai: Status phase/Completed, mulai & berakhir di masa lalu.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status_id' => self::phaseStatusId('Completed'),
            'started_at' => now()->subDays(20),
            'ended_at' => now()->subDays(10),
        ]);
    }

    /**
     * Fase mendatang: Status phase/Pending, belum mulai.
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status_id' => self::phaseStatusId('Pending'),
            'started_at' => null,
            'ended_at' => null,
        ]);
    }

    /**
     * ID Status ber-`type='phase'` untuk nama tertentu (Pending/Active/Completed).
     * Dibuat bila belum ada agar factory tetap jalan tanpa StatusSeeder.
     */
    public static function phaseStatusId(string $name): int
    {
        return Status::where('type', 'phase')->where('name', $name)->value('id')
            ?? Status::factory()->create(['name' => $name, 'type' => 'phase'])->id;
    }
}
