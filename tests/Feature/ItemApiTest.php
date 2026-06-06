<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Items;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji CRUD barang owner-scoped (lewat gudang): list per gudang, 404
 * lintas-user, resolusi kategori (id & nama), stok default 0, serta
 * update/destroy yang owner-scoped via gudang.
 *
 * Notification::fake() dipasang karena ItemsObserver memicu LowStockNotification.
 */
class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ItemsObserver mengirim LowStockNotification saat stok < threshold.
        Notification::fake();
    }

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    public function test_index_returns_only_items_of_owned_warehouse(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);

        $category = Categories::factory()->create(['name' => 'Pupuk']);
        Items::factory()->count(2)->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/warehouses/{$warehouse->id}/items")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.warehouse_id', $warehouse->id)
            ->assertJsonPath('data.0.category', 'Pupuk');
    }

    public function test_index_returns_404_for_other_users_warehouse(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Warehouse::factory()->create(['farmer_id' => $other->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/warehouses/{$foreign->id}/items")
            ->assertNotFound();
    }

    public function test_store_persists_item_with_category_id_and_defaults_stock_to_zero(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);
        $category = Categories::factory()->create(['name' => 'Bibit']);

        Sanctum::actingAs($user);

        $this->postJson("/api/warehouses/{$warehouse->id}/items", [
            'name' => 'Benih Cabai',
            'unit' => 'pack',
            'category_id' => $category->id,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Benih Cabai')
            ->assertJsonPath('data.unit', 'pack')
            ->assertJsonPath('data.stock', 0)
            ->assertJsonPath('data.category', 'Bibit')
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.warehouse_id', $warehouse->id);

        $this->assertDatabaseHas('items', [
            'name' => 'Benih Cabai',
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'stock' => 0,
        ]);
    }

    public function test_store_resolves_category_by_name(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/warehouses/{$warehouse->id}/items", [
            'name' => 'Pupuk Urea',
            'unit' => 'kg',
            'stock' => 40,
            'category' => 'Pupuk',
        ])->assertCreated()
            ->assertJsonPath('data.category', 'Pupuk')
            ->assertJsonPath('data.stock', 40);

        $this->assertDatabaseHas('categories', ['name' => 'Pupuk']);
    }

    public function test_store_returns_404_for_other_users_warehouse(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Warehouse::factory()->create(['farmer_id' => $other->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/warehouses/{$foreign->id}/items", [
            'name' => 'Selundupan',
            'unit' => 'kg',
        ])->assertNotFound();
    }

    public function test_store_requires_name_and_unit(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/warehouses/{$warehouse->id}/items", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'unit']);
    }

    public function test_update_owner_scoped_changes_fields(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);
        $category = Categories::factory()->create(['name' => 'Alat']);
        $item = Items::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'name' => 'Lama',
            'stock' => 100,
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/items/{$item->id}", [
            'name' => 'Baru',
            'stock' => 75,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Baru')
            ->assertJsonPath('data.stock', 75);

        $this->assertSame('Baru', $item->fresh()->name);
        $this->assertSame(75, $item->fresh()->stock);
    }

    public function test_update_returns_404_for_other_users_item(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignWarehouse = Warehouse::factory()->create(['farmer_id' => $other->id]);
        $category = Categories::factory()->create();
        $foreignItem = Items::factory()->create([
            'warehouse_id' => $foreignWarehouse->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/items/{$foreignItem->id}", ['name' => 'Hijack'])
            ->assertNotFound();
    }

    public function test_destroy_owner_scoped_removes_item(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);
        $category = Categories::factory()->create();
        $item = Items::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_destroy_returns_404_for_other_users_item(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignWarehouse = Warehouse::factory()->create(['farmer_id' => $other->id]);
        $category = Categories::factory()->create();
        $foreignItem = Items::factory()->create([
            'warehouse_id' => $foreignWarehouse->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/items/{$foreignItem->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('items', ['id' => $foreignItem->id]);
    }
}
