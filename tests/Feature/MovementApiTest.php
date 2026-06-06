<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Items;
use App\Models\Movements;
use App\Models\MoveTypes;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji kontrak API transaksi stok (movements):
 * - GET /warehouses/{id}/movements (riwayat owner-scoped, terbaru dulu).
 * - POST /movements (IN menaikkan stok, OUT menurunkan, OUT > stok -> 422 rollback,
 *   owner-scope item gudang orang lain -> 404).
 */
class MovementApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    private function seedMovementRefs(): void
    {
        MoveTypes::insert([
            ['name' => 'Barang Masuk', 'code' => 'IN'],
            ['name' => 'Barang Keluar', 'code' => 'OUT'],
            ['name' => 'Transfer', 'code' => 'TRANSFER'],
        ]);

        Status::insert([
            ['name' => 'In Progress', 'type' => 'movement'],
            ['name' => 'Done', 'type' => 'movement'],
        ]);
    }

    /**
     * Buat item dengan stok tertentu di gudang milik $owner.
     */
    private function makeItem(User $owner, int $stock = 40): Items
    {
        $warehouse = Warehouse::factory()->create(['farmer_id' => $owner->id]);
        $category = Categories::factory()->create(['name' => 'Pupuk']);

        return Items::create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'name' => 'Pupuk Urea',
            'unit' => 'kg',
            'stock' => $stock,
        ]);
    }

    public function test_movements_require_authentication(): void
    {
        $this->postJson('/api/movements')->assertUnauthorized();
        $this->getJson('/api/warehouses/1/movements')->assertUnauthorized();
    }

    public function test_store_in_increases_item_stock_and_creates_movement(): void
    {
        Notification::fake();
        $this->seedRoles();
        $this->seedMovementRefs();

        $farmer = User::factory()->create();
        $item = $this->makeItem($farmer, 40);
        Sanctum::actingAs($farmer);

        $this->postJson('/api/movements', [
            'item_id' => $item->id,
            'type' => 'in',
            'quantity' => 10,
            'note' => 'Pembelian baru',
        ])
            ->assertCreated()
            ->assertJsonPath('data.direction', 'Masuk')
            ->assertJsonPath('data.qty', 10)
            ->assertJsonPath('data.item_name', 'Pupuk Urea')
            ->assertJsonPath('data.category', 'Pupuk')
            ->assertJsonPath('data.status', 'Done');

        $this->assertSame(50, $item->fresh()->stock);
        $this->assertSame(1, Movements::where('item_id', $item->id)->count());
    }

    public function test_store_out_decreases_item_stock_when_sufficient(): void
    {
        Notification::fake();
        $this->seedRoles();
        $this->seedMovementRefs();

        $farmer = User::factory()->create();
        $item = $this->makeItem($farmer, 40);
        Sanctum::actingAs($farmer);

        $this->postJson('/api/movements', [
            'item_id' => $item->id,
            'type' => 'out',
            'quantity' => 10,
            'note' => 'Dipakai lahan utara',
        ])
            ->assertCreated()
            ->assertJsonPath('data.direction', 'Keluar');

        $this->assertSame(30, $item->fresh()->stock);
    }

    public function test_store_out_exceeding_stock_returns_422_and_rolls_back(): void
    {
        Notification::fake();
        $this->seedRoles();
        $this->seedMovementRefs();

        $farmer = User::factory()->create();
        $item = $this->makeItem($farmer, 30);
        Sanctum::actingAs($farmer);

        $this->postJson('/api/movements', [
            'item_id' => $item->id,
            'type' => 'out',
            'quantity' => 999,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');

        // Stok tidak berubah & tidak ada baris movement (rollback transaksi).
        $this->assertSame(30, $item->fresh()->stock);
        $this->assertSame(0, Movements::where('item_id', $item->id)->count());
    }

    public function test_store_returns_404_for_item_in_other_users_warehouse(): void
    {
        Notification::fake();
        $this->seedRoles();
        $this->seedMovementRefs();

        $owner = User::factory()->create();
        $item = $this->makeItem($owner, 40);

        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $this->postJson('/api/movements', [
            'item_id' => $item->id,
            'type' => 'out',
            'quantity' => 5,
        ])->assertNotFound();

        // Stok milik owner tidak tersentuh.
        $this->assertSame(40, $item->fresh()->stock);
    }

    public function test_index_returns_owner_scoped_movements_latest_first(): void
    {
        Notification::fake();
        $this->seedRoles();
        $this->seedMovementRefs();

        $farmer = User::factory()->create();
        $item = $this->makeItem($farmer, 40);
        Sanctum::actingAs($farmer);

        // Dua transaksi berurutan via endpoint.
        $this->postJson('/api/movements', ['item_id' => $item->id, 'type' => 'in', 'quantity' => 5]);
        $this->postJson('/api/movements', ['item_id' => $item->id, 'type' => 'out', 'quantity' => 3]);

        $this->getJson("/api/warehouses/{$item->warehouse_id}/movements")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'date', 'item_id', 'item_name', 'category', 'qty', 'direction', 'note', 'status', 'created_at']],
            ])
            // Terbaru dulu: transaksi OUT (terakhir) ada di paling atas.
            ->assertJsonPath('data.0.direction', 'Keluar');
    }

    public function test_index_returns_404_for_other_users_warehouse(): void
    {
        $this->seedRoles();
        $owner = User::factory()->create();
        $item = $this->makeItem($owner, 40);

        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $this->getJson("/api/warehouses/{$item->warehouse_id}/movements")
            ->assertNotFound();
    }
}
