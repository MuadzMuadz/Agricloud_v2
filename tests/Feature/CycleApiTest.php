<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Cycle;
use App\Models\Land;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji kontrak Cycle-Backend: list siklus, owner-scope 404, active_cycle, & store.
 */
class CycleApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    /** Status dibuat via factory agar kolom `type` ikut terisi (Status::create melewatinya). */
    private function makeStatus(string $name, string $type): Status
    {
        return Status::factory()->create(['name' => $name, 'type' => $type]);
    }

    public function test_cycles_index_returns_mapped_status_phase_and_progress(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $activeCycle = $this->makeStatus('Active', 'cycle');
        $activePhase = $this->makeStatus('Active', 'phase');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create(['name' => 'Cabai Merah']);
        $stage = Stage::factory()->create(['crop_id' => $crop->id, 'name' => 'Pembungaan']);

        $cycle = Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => $activeCycle->id,
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
        ]);

        Phase::factory()->create([
            'cycle_id' => $cycle->id,
            'stage_id' => $stage->id,
            'status_id' => $activePhase->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/cycles?field_id='.$land->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.plant_name', 'Cabai Merah')
            ->assertJsonPath('data.0.field_id', $land->id)
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.0.phase', 'Pembungaan');

        // Progress diturunkan dari rentang tanggal: 40 dari 100 hari ≈ 40%.
        $progress = $response->json('data.0.progress');
        $this->assertNotNull($progress);
        $this->assertGreaterThan(0, $progress);
        $this->assertLessThan(100, $progress);
    }

    public function test_cycles_index_returns_404_for_land_of_other_user(): void
    {
        $this->seedRoles();
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $land = Land::factory()->create(['farmer_id' => $owner->id]);

        Sanctum::actingAs($other);

        $this->getJson('/api/cycles?field_id='.$land->id)
            ->assertNotFound();
    }

    public function test_cycles_index_requires_field_id(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/cycles')
            ->assertStatus(422)
            ->assertJsonValidationErrors('field_id');
    }

    public function test_myfields_show_returns_active_cycle_when_present(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $activeCycle = $this->makeStatus('Active', 'cycle');
        $activePhase = $this->makeStatus('Active', 'phase');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create(['name' => 'Tomat']);
        $stage = Stage::factory()->create(['crop_id' => $crop->id, 'name' => 'Vegetatif']);

        $cycle = Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => $activeCycle->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        Phase::factory()->create([
            'cycle_id' => $cycle->id,
            'stage_id' => $stage->id,
            'status_id' => $activePhase->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/myfields/'.$land->id)
            ->assertOk()
            ->assertJsonPath('data.id', $land->id)
            ->assertJsonPath('data.active_cycle.plant_name', 'Tomat')
            ->assertJsonPath('data.active_cycle.phase', 'Vegetatif');

        // Tepat di tengah rentang 20 hari → ~50% (toleransi ±2 untuk bagian jam hari ini).
        $progress = $response->json('data.active_cycle.progress');
        $this->assertGreaterThanOrEqual(48, $progress);
        $this->assertLessThanOrEqual(52, $progress);
    }

    public function test_myfields_show_active_cycle_null_without_active_cycle(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $completed = $this->makeStatus('Completed', 'cycle');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create(['name' => 'Jagung']);

        // Hanya siklus selesai → active_cycle null.
        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => $completed->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/myfields/'.$land->id)
            ->assertOk()
            ->assertJsonPath('data.active_cycle', null);
    }

    public function test_myfields_show_returns_404_for_land_of_other_user(): void
    {
        $this->seedRoles();
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $land = Land::factory()->create(['farmer_id' => $owner->id]);

        Sanctum::actingAs($other);

        $this->getJson('/api/myfields/'.$land->id)
            ->assertNotFound();
    }

    public function test_store_creates_cycle_and_returns_201(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $this->makeStatus('Active', 'cycle');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create(['name' => 'Cabai Rawit']);

        Sanctum::actingAs($user);

        $this->postJson('/api/cycles', [
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'name' => 'Musim 1',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.plant_name', 'Cabai Rawit')
            ->assertJsonPath('data.field_id', $land->id)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('cycles', [
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'name' => 'Musim 1',
        ]);
    }

    public function test_store_auto_fills_end_date_and_seeds_initial_phase(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $this->makeStatus('Active', 'cycle');
        $this->makeStatus('Active', 'phase');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create(['name' => 'Cabai']);

        // Stages per crop (Penyemaian order terkecil) — total 7+14+5 = 26 hari.
        Stage::factory()->create(['crop_id' => $crop->id, 'name' => 'Penyemaian', 'order' => 1, 'duration_days' => 7]);
        Stage::factory()->create(['crop_id' => $crop->id, 'name' => 'Pertumbuhan', 'order' => 2, 'duration_days' => 14]);
        Stage::factory()->create(['crop_id' => $crop->id, 'name' => 'Panen', 'order' => 3, 'duration_days' => 5]);

        $startDate = now()->subDays(5)->toDateString();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/cycles', [
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'name' => 'Cabai Custom',
            'start_date' => $startDate,
        ])->assertCreated()
            ->assertJsonPath('data.plant_name', 'Cabai')
            ->assertJsonPath('data.name', 'Cabai Custom')
            ->assertJsonPath('data.phase', 'Penyemaian');

        // end_date = start + 26 hari → estimated_harvest_date terisi.
        $expectedEnd = Carbon::parse($startDate)->addDays(26)->toDateString();
        $response->assertJsonPath('data.estimated_harvest_date', $expectedEnd);

        // progress numerik (start 5 hari lalu dari rentang 26 hari).
        $progress = $response->json('data.progress');
        $this->assertNotNull($progress);
        $this->assertGreaterThan(0, $progress);
        $this->assertLessThan(100, $progress);

        $this->assertDatabaseHas('cycles', [
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'end_date' => $expectedEnd,
        ]);
    }

    public function test_store_keeps_explicit_end_date_and_returns_custom_name(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $this->makeStatus('Active', 'cycle');
        $this->makeStatus('Active', 'phase');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create(['name' => 'Tomat']);
        Stage::factory()->create(['crop_id' => $crop->id, 'name' => 'Penyemaian', 'order' => 1, 'duration_days' => 7]);

        $explicitEnd = now()->addDays(90)->toDateString();

        Sanctum::actingAs($user);

        $this->postJson('/api/cycles', [
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'name' => 'Musim Tomat',
            'start_date' => now()->toDateString(),
            'end_date' => $explicitEnd,
        ])->assertCreated()
            ->assertJsonPath('data.plant_name', 'Tomat')
            ->assertJsonPath('data.name', 'Musim Tomat')
            ->assertJsonPath('data.estimated_harvest_date', $explicitEnd)
            ->assertJsonPath('data.phase', 'Penyemaian');
    }

    public function test_store_degrades_gracefully_when_crop_has_no_stage(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $this->makeStatus('Active', 'cycle');
        $this->makeStatus('Active', 'phase');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create(['name' => 'Selada']);

        Sanctum::actingAs($user);

        // Tanpa stage → end_date & phase tetap null, tanpa error.
        $this->postJson('/api/cycles', [
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'name' => 'Selada Custom',
            'start_date' => now()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Selada Custom')
            ->assertJsonPath('data.estimated_harvest_date', null)
            ->assertJsonPath('data.phase', null)
            ->assertJsonPath('data.progress', null);

        $this->assertDatabaseMissing('phases', ['cycle_id' => Cycle::where('name', 'Selada Custom')->value('id')]);
    }

    public function test_store_returns_404_for_land_of_other_user(): void
    {
        $this->seedRoles();
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->makeStatus('Active', 'cycle');

        $land = Land::factory()->create(['farmer_id' => $owner->id]);
        $crop = Crop::factory()->create(['name' => 'Bayam']);

        Sanctum::actingAs($other);

        $this->postJson('/api/cycles', [
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'name' => 'Curang',
        ])->assertNotFound();
    }
}
