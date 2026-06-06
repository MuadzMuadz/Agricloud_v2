<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Items;
use App\Models\Movements;
use App\Models\MoveTypes;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji CRUD gudang owner-scoped: index milik user, show 404 lintas-user,
 * store multipart + thumbnail + farmer_id, items_count, dan update.
 */
class WarehouseApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    public function test_index_returns_only_owned_warehouses_with_items_count(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = Warehouse::factory()->create(['farmer_id' => $user->id]);
        Warehouse::factory()->create(['farmer_id' => $other->id]);

        $category = Categories::factory()->create();
        Items::factory()->count(3)->create([
            'warehouse_id' => $mine->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/warehouses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.items_count', 3)
            ->assertJsonPath('data.0.owner.id', $user->id);
    }

    public function test_show_returns_404_for_other_users_warehouse(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $foreign = Warehouse::factory()->create(['farmer_id' => $other->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/warehouses/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_store_persists_warehouse_with_thumbnail_and_owner(): void
    {
        Storage::fake('public');
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/warehouses', [
            'name' => 'Gudang Utama',
            'address' => 'Jl. Sawah No.1',
            'capacity' => 1000,
            'latitude' => -6.7,
            'longitude' => 108.55,
            // create() (bukan image()) agar tak butuh ekstensi GD; mime image/jpeg lolos rule `image`.
            'thumbnail' => UploadedFile::fake()->create('gudang.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Gudang Utama')
            ->assertJsonPath('data.address', 'Jl. Sawah No.1')
            ->assertJsonPath('data.capacity', 1000)
            ->assertJsonPath('data.location.latitude', -6.7)
            ->assertJsonPath('data.owner.id', $user->id);

        $this->assertNotNull($response->json('data.thumbnail'));

        $warehouse = Warehouse::where('name', 'Gudang Utama')->first();
        $this->assertSame($user->id, $warehouse->farmer_id);
        $this->assertSame('Jl. Sawah No.1', $warehouse->location);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', parse_url($warehouse->image_url, PHP_URL_PATH)));
    }

    public function test_store_requires_name(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/warehouses', ['address' => 'Tanpa Nama'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_update_owner_scoped_changes_fields(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $warehouse = Warehouse::factory()->create([
            'farmer_id' => $user->id,
            'name' => 'Lama',
        ]);

        $this->putJson("/api/warehouses/{$warehouse->id}", [
            'name' => 'Baru',
            'capacity' => 500,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Baru')
            ->assertJsonPath('data.capacity', 500);

        $this->assertSame('Baru', $warehouse->fresh()->name);
    }

    public function test_update_returns_404_for_other_users_warehouse(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        $foreign = Warehouse::factory()->create(['farmer_id' => $other->id]);

        $this->putJson("/api/warehouses/{$foreign->id}", ['name' => 'Hijack'])
            ->assertNotFound();
    }

    public function test_index_returns_used_as_sum_of_item_stock(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $warehouse = Warehouse::factory()->create([
            'farmer_id' => $user->id,
            'capacity' => 1000,
        ]);

        $category = Categories::factory()->create();
        Items::factory()->create(['warehouse_id' => $warehouse->id, 'category_id' => $category->id, 'stock' => 300]);
        Items::factory()->create(['warehouse_id' => $warehouse->id, 'category_id' => $category->id, 'stock' => 520]);

        Sanctum::actingAs($user);

        $this->getJson('/api/warehouses')
            ->assertOk()
            ->assertJsonPath('data.0.used', 820)
            ->assertJsonPath('data.0.capacity', 1000);
    }

    public function test_show_embeds_items_collection(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);
        $category = Categories::factory()->create(['name' => 'Pupuk']);
        Items::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'name' => 'Urea',
            'unit' => 'kg',
            'stock' => 50,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/warehouses/{$warehouse->id}")
            ->assertOk()
            ->assertJsonPath('data.used', 50)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.name', 'Urea')
            ->assertJsonPath('data.items.0.unit', 'kg')
            ->assertJsonPath('data.items.0.category', 'Pupuk');
    }

    public function test_index_does_not_embed_items_collection(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);
        $category = Categories::factory()->create();
        Items::factory()->create(['warehouse_id' => $warehouse->id, 'category_id' => $category->id]);

        Sanctum::actingAs($user);

        // Di list relasi Items tidak di-load → `items` tidak ikut (whenLoaded).
        $this->getJson('/api/warehouses')
            ->assertOk()
            ->assertJsonMissingPath('data.0.items');
    }

    public function test_destroy_removes_warehouse_and_cascades_items_and_movements(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);
        $category = Categories::factory()->create();
        $item = Items::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
        ]);
        $movetype = MoveTypes::factory()->create();
        $movement = Movements::factory()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'movetype_id' => $movetype->id,
            'status_id' => null,
            'land_dest' => null,
            'warehouse_dest' => null,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/warehouses/{$warehouse->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Gudang berhasil dihapus');

        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
        $this->assertDatabaseMissing('movements', ['id' => $movement->id]);
    }

    public function test_destroy_returns_403_for_other_users_warehouse(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $foreign = Warehouse::factory()->create(['farmer_id' => $other->id]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/warehouses/{$foreign->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('warehouses', ['id' => $foreign->id]);
    }

    public function test_destroy_returns_404_for_missing_warehouse(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/warehouses/999999')
            ->assertNotFound();
    }
}
