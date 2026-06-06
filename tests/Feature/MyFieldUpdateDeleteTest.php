<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Cycle;
use App\Models\Land;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji kontrak Field-UpdateDelete: PUT & DELETE /myfields/{id}
 * (owner-check 404/403, validasi, thumbnail, 409 siklus aktif).
 */
class MyFieldUpdateDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    public function test_update_changes_fields_and_returns_200(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $land = Land::factory()->create([
            'farmer_id' => $user->id,
            'name' => 'Lahan Lama',
            'area' => 1000,
        ]);

        Sanctum::actingAs($user);

        $boundary = [[-6.70, 108.55], [-6.701, 108.551], [-6.702, 108.55]];

        $this->putJson('/api/myfields/'.$land->id, [
            'name' => 'Lahan Baru',
            'area' => '2500.00',
            'boundary' => json_encode($boundary),
        ])->assertOk()
            ->assertJsonPath('data.id', $land->id)
            ->assertJsonPath('data.name', 'Lahan Baru')
            ->assertJsonPath('data.boundary', $boundary);

        $this->assertDatabaseHas('lands', [
            'id' => $land->id,
            'name' => 'Lahan Baru',
        ]);
        $this->assertSame($boundary, $land->fresh()->boundary);
    }

    public function test_update_keeps_existing_thumbnail_when_no_file_sent(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $land = Land::factory()->create([
            'farmer_id' => $user->id,
            'image_url' => '/storage/lands/lama.jpg',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/myfields/'.$land->id, ['name' => 'Tanpa Foto'])
            ->assertOk();

        $this->assertSame('/storage/lands/lama.jpg', $land->fresh()->image_url);
    }

    public function test_update_replaces_thumbnail_and_deletes_old_file(): void
    {
        Storage::fake('public');

        $this->seedRoles();
        $user = User::factory()->create();

        // Siapkan file lama nyata di disk public. Pakai create()+mime (bukan image())
        // karena ekstensi GD tidak tersedia di lingkungan tes.
        $oldPath = UploadedFile::fake()->create('lama.jpg', 10, 'image/jpeg')->store('lands', 'public');
        $land = Land::factory()->create([
            'farmer_id' => $user->id,
            'image_url' => '/storage/'.$oldPath,
        ]);
        Storage::disk('public')->assertExists($oldPath);

        Sanctum::actingAs($user);

        // POST + _method=PUT (multipart spoofing) seperti FE.
        $response = $this->post('/api/myfields/'.$land->id, [
            '_method' => 'PUT',
            'thumbnail' => UploadedFile::fake()->create('baru.jpg', 10, 'image/jpeg'),
        ]);
        $response->assertOk();

        $newUrl = $land->fresh()->image_url;
        $this->assertNotSame('/storage/'.$oldPath, $newUrl);

        $newPath = preg_replace('#^/storage/#', '', $newUrl);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath); // file lama terhapus
    }

    public function test_update_returns_403_for_land_of_other_user(): void
    {
        $this->seedRoles();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $land = Land::factory()->create(['farmer_id' => $owner->id]);

        Sanctum::actingAs($other);

        $this->putJson('/api/myfields/'.$land->id, ['name' => 'Curang'])
            ->assertStatus(403);
    }

    public function test_update_returns_404_for_missing_land(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/myfields/999999', ['name' => 'X'])
            ->assertNotFound();
    }

    public function test_update_rejects_non_numeric_area(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $land = Land::factory()->create(['farmer_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->putJson('/api/myfields/'.$land->id, ['area' => 'bukan-angka'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('area');
    }

    public function test_destroy_deletes_land_and_returns_200(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $land = Land::factory()->create(['farmer_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/myfields/'.$land->id)
            ->assertOk()
            ->assertJsonPath('message', 'Lahan berhasil dihapus');

        $this->assertDatabaseMissing('lands', ['id' => $land->id]);

        // Hilang dari index.
        $this->getJson('/api/myfields')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_destroy_returns_403_for_land_of_other_user(): void
    {
        $this->seedRoles();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $land = Land::factory()->create(['farmer_id' => $owner->id]);

        Sanctum::actingAs($other);

        $this->deleteJson('/api/myfields/'.$land->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('lands', ['id' => $land->id]);
    }

    public function test_destroy_returns_409_when_land_has_active_cycle(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $activeCycle = Status::factory()->create(['name' => 'Active', 'type' => 'cycle']);
        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create();

        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => $activeCycle->id,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/myfields/'.$land->id)
            ->assertStatus(409);

        $this->assertDatabaseHas('lands', ['id' => $land->id]);
    }
}
