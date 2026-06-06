<?php

namespace Tests\Unit;

use App\Models\Cycle;
use App\Models\Items;
use App\Models\Land;
use App\Models\Movements;
use App\Models\Phase;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

/**
 * Menguji definisi relasi Eloquent (tanpa DB).
 * Instansiasi relasi akan mem-autoload model terkait — berguna untuk
 * menangkap salah penamaan class (mis. MoveTypes vs Movetypes).
 */
class ModelRelationshipTest extends TestCase
{
    public function test_user_relationships(): void
    {
        $user = new User;
        $this->assertInstanceOf(BelongsTo::class, $user->Role());
        $this->assertInstanceOf(HasMany::class, $user->Lands());
        $this->assertSame('farmer_id', $user->Lands()->getForeignKeyName());
        $this->assertInstanceOf(HasMany::class, $user->Warehouses());
        $this->assertSame('farmer_id', $user->Warehouses()->getForeignKeyName());
    }

    public function test_land_relationships(): void
    {
        $land = new Land;
        $this->assertInstanceOf(BelongsTo::class, $land->Farmer());
        $this->assertSame('farmer_id', $land->Farmer()->getForeignKeyName());
        $this->assertInstanceOf(HasMany::class, $land->Cycles());
    }

    public function test_cycle_relationships(): void
    {
        $cycle = new Cycle;
        $this->assertInstanceOf(BelongsTo::class, $cycle->Land());
        $this->assertInstanceOf(BelongsTo::class, $cycle->Crop());
        $this->assertInstanceOf(BelongsTo::class, $cycle->Status());
        $this->assertInstanceOf(HasMany::class, $cycle->Phases());
    }

    public function test_phase_relationships(): void
    {
        $phase = new Phase;
        $this->assertInstanceOf(BelongsTo::class, $phase->Cycle());
        $this->assertInstanceOf(BelongsTo::class, $phase->Stage());
        $this->assertInstanceOf(HasMany::class, $phase->Needs());
    }

    public function test_warehouse_relationships(): void
    {
        $warehouse = new Warehouse;
        $this->assertInstanceOf(BelongsTo::class, $warehouse->Farmer());
        $this->assertInstanceOf(HasMany::class, $warehouse->Items());
    }

    public function test_items_relationships(): void
    {
        $item = new Items;
        $this->assertInstanceOf(BelongsTo::class, $item->Warehouse());
        $this->assertInstanceOf(BelongsTo::class, $item->Category());
    }

    /**
     * Relasi ini me-reference Movetypes::class. Bila nama/berkas class salah huruf,
     * autoload pada filesystem case-sensitive (Linux) akan gagal di sini.
     */
    public function test_movement_relationships(): void
    {
        $movement = new Movements;
        $this->assertInstanceOf(BelongsTo::class, $movement->Warehouse());
        $this->assertInstanceOf(BelongsTo::class, $movement->Item());
        $this->assertInstanceOf(BelongsTo::class, $movement->Movetype());
        $this->assertInstanceOf(BelongsTo::class, $movement->Status());
        $this->assertInstanceOf(BelongsTo::class, $movement->LandDest());
        $this->assertSame('land_dest', $movement->LandDest()->getForeignKeyName());
    }
}
