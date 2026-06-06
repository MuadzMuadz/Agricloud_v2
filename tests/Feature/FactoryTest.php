<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Crop;
use App\Models\Cycle;
use App\Models\Items;
use App\Models\Land;
use App\Models\Movements;
use App\Models\MoveTypes;
use App\Models\Needs;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Status;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji tiap factory bisa membuat record valid.
 * Tiap factory diberi prasyarat (relasi induk) yang valid lebih dulu,
 * sehingga kegagalan menunjuk langsung ke factory yang bersangkutan.
 */
class FactoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Buat role admin + farmer, kembalikan role farmer.
     * Catatan: sequence id Postgres tidak di-reset oleh rollback RefreshDatabase,
     * jadi id role berubah antar-test — selalu pakai id aktual, jangan hardcode.
     */
    private function farmerRole(): Role
    {
        Role::create(['name' => 'admin']);

        return Role::create(['name' => 'farmer']);
    }

    private function farmer(): User
    {
        return User::factory()->create(['role_id' => $this->farmerRole()->id]);
    }

    public function test_role_factory(): void
    {
        $this->assertDatabaseCount('roles', 0);
        Role::factory()->create();
        $this->assertDatabaseCount('roles', 1);
    }

    public function test_user_factory(): void
    {
        $user = $this->farmer();
        $this->assertNotNull($user->id);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_crop_factory(): void
    {
        $crop = Crop::factory()->create();
        $this->assertDatabaseHas('crops', ['id' => $crop->id]);
    }

    public function test_status_factory(): void
    {
        $status = Status::factory()->create();
        $this->assertDatabaseHas('statuses', ['id' => $status->id]);
    }

    public function test_land_factory(): void
    {
        $this->farmer();
        $land = Land::factory()->create();
        $this->assertDatabaseHas('lands', ['id' => $land->id]);
    }

    public function test_stage_factory(): void
    {
        Crop::factory()->create();
        $stage = Stage::factory()->create();
        $this->assertDatabaseHas('stages', ['id' => $stage->id]);
    }

    public function test_cycle_factory(): void
    {
        $this->farmer();
        Land::factory()->create();
        Crop::factory()->create();
        Status::factory()->create();

        $cycle = Cycle::factory()->create();
        $this->assertDatabaseHas('cycles', ['id' => $cycle->id]);
    }

    public function test_phase_factory(): void
    {
        $this->farmer();
        Land::factory()->create();
        $crop = Crop::factory()->create();
        Status::factory()->create();
        Stage::factory()->create(['crop_id' => $crop->id]);
        Cycle::factory()->create(['crop_id' => $crop->id]);

        $phase = Phase::factory()->create();
        $this->assertDatabaseHas('phases', ['id' => $phase->id]);
    }

    public function test_warehouse_factory(): void
    {
        // Warehouse factory cari user ber-role 'farmer'
        $this->farmer();
        $warehouse = Warehouse::factory()->create();
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }

    public function test_categories_factory(): void
    {
        $cat = Categories::factory()->create();
        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
    }

    public function test_items_factory(): void
    {
        $this->farmer();
        Warehouse::factory()->create();
        Categories::factory()->create();

        $item = Items::factory()->create();
        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_movetypes_factory(): void
    {
        $type = MoveTypes::factory()->create();
        $this->assertDatabaseHas('move_types', ['id' => $type->id]);
    }

    public function test_movements_factory(): void
    {
        $this->farmer();
        $warehouse = Warehouse::factory()->create();
        Categories::factory()->create();
        Items::factory()->create();
        MoveTypes::factory()->create();
        Status::factory()->create();

        $movement = Movements::factory()->create();
        $this->assertDatabaseHas('movements', ['id' => $movement->id]);
    }

    public function test_needs_factory(): void
    {
        $this->farmer();
        Land::factory()->create();
        $crop = Crop::factory()->create();
        Status::factory()->create();
        Stage::factory()->create(['crop_id' => $crop->id]);
        Cycle::factory()->create(['crop_id' => $crop->id]);
        Phase::factory()->create();
        Warehouse::factory()->create();
        Categories::factory()->create();
        Items::factory()->create();

        $need = Needs::factory()->create();
        $this->assertDatabaseHas('needs', ['id' => $need->id]);
    }
}
