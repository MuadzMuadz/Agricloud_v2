<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Cycle;
use App\Models\Land;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji enhancement kontrak lahan: crops aktif per lahan & boundary poligon.
 */
class MyFieldEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    public function test_myfields_index_returns_multiple_active_crops(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        // Factory dipakai agar kolom `type` ikut terisi (Status::create melewatinya).
        $activeCycle = Status::factory()->create(['name' => 'Active', 'type' => 'cycle']);

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $cabai = Crop::factory()->create(['name' => 'Cabai']);
        $kacang = Crop::factory()->create(['name' => 'Kacang Tanah']);

        // Dua siklus aktif (tumpang sari) pada satu lahan.
        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $cabai->id,
            'status_id' => $activeCycle->id,
        ]);
        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $kacang->id,
            'status_id' => $activeCycle->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/myfields')
            ->assertOk()
            ->assertJsonCount(2, 'data.0.crops')
            ->assertJsonFragment(['name' => 'Cabai'])
            ->assertJsonFragment(['name' => 'Kacang Tanah']);
    }

    public function test_myfields_index_excludes_non_active_cycles(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $completedCycle = Status::factory()->create(['name' => 'Completed', 'type' => 'cycle']);

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create(['name' => 'Tomat']);

        // Hanya siklus selesai → crops harus kosong.
        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => $completedCycle->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/myfields')
            ->assertOk()
            ->assertJsonCount(0, 'data.0.crops');
    }

    public function test_myfields_store_persists_and_returns_boundary(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $boundary = [[-6.70, 108.55], [-6.701, 108.551], [-6.702, 108.55]];

        $response = $this->postJson('/api/myfields', [
            'name' => 'Lahan Boundary',
            'area' => '1500.00',
            'boundary' => json_encode($boundary),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Lahan Boundary')
            ->assertJsonPath('data.boundary', $boundary)
            ->assertJsonCount(0, 'data.crops');

        $land = Land::where('name', 'Lahan Boundary')->first();
        $this->assertSame($boundary, $land->boundary);
    }

    public function test_myfields_store_rejects_non_numeric_area(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/myfields', [
            'name' => 'Lahan Invalid',
            'area' => 'bukan-angka',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('area');
    }

    public function test_myfields_store_rejects_invalid_boundary_json(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/myfields', [
            'name' => 'Lahan Invalid Boundary',
            'boundary' => 'bukan-json',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('boundary');
    }
}
