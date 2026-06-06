<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Crop;
use App\Models\Cycle;
use App\Models\Items;
use App\Models\Land;
use App\Models\Needs;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Status;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji kontrak Dashboard-TasksAlerts (A-lite, computed):
 * GET /api/tasks menurunkan tindakan panen/input/restock dari domain milik user,
 * GET /api/dashboard/summary mengembalikan attention_count.
 */
class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'farmer']);
    }

    /** Status dibuat via factory agar kolom `type` ikut terisi (Status::create melewatinya). */
    private function makeStatus(string $name, string $type): Status
    {
        return Status::factory()->create(['name' => $name, 'type' => $type]);
    }

    public function test_tasks_requires_authentication(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
    }

    public function test_tasks_returns_empty_array_without_data(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertExactJson(['data' => []]);
    }

    public function test_harvest_task_appears_when_end_date_is_near(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $this->makeStatus('Active', 'cycle');

        $land = Land::factory()->create(['farmer_id' => $user->id, 'name' => 'Lahan A']);
        $crop = Crop::factory()->create(['name' => 'Anggur']);

        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => Status::where('type', 'cycle')->where('name', 'Active')->value('id'),
            'start_date' => now()->subDays(80)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(), // dalam jangkauan default 7 hari
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tasks')->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'harvest')
            ->assertJsonPath('data.0.field_id', $land->id)
            ->assertJsonPath('data.0.urgency', 'urgent');
    }

    public function test_harvest_task_hidden_when_end_date_is_far(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $this->makeStatus('Active', 'cycle');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create();

        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => Status::where('type', 'cycle')->where('name', 'Active')->value('id'),
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(), // di luar jangkauan
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_restock_task_appears_when_stock_is_low(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);
        $category = Categories::factory()->create();

        // Stok di bawah threshold default (10).
        Items::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'name' => 'Pupuk Urea',
            'stock' => 3,
        ]);
        // Stok cukup → tidak memunculkan task.
        Items::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'name' => 'Benih',
            'stock' => 50,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tasks')->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'restock')
            ->assertJsonPath('data.0.field_id', null)
            ->assertJsonPath('data.0.urgency', 'urgent');
    }

    public function test_input_task_appears_for_unmet_need_on_active_phase(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $this->makeStatus('Active', 'cycle');
        $this->makeStatus('Active', 'phase');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create();
        $stage = Stage::factory()->create(['crop_id' => $crop->id]);

        $cycle = Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => Status::where('type', 'cycle')->where('name', 'Active')->value('id'),
            'end_date' => now()->addDays(60)->toDateString(),
        ]);

        $phase = Phase::factory()->create([
            'cycle_id' => $cycle->id,
            'stage_id' => $stage->id,
            'status_id' => Status::where('type', 'phase')->where('name', 'Active')->value('id'),
        ]);

        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);
        $category = Categories::factory()->create();
        // Stok cukup banyak agar tidak ikut memicu restock, tapi kurang dari kebutuhan.
        $item = Items::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'stock' => 30,
        ]);

        Needs::factory()->create([
            'phase_id' => $phase->id,
            'item_id' => $item->id,
            'quantity_needed' => 100, // 30 < 100 → belum terpenuhi
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tasks')->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'input')
            ->assertJsonPath('data.0.field_id', $land->id)
            ->assertJsonPath('data.0.urgency', 'due');
    }

    public function test_does_not_leak_other_users_tasks(): void
    {
        $this->seedRoles();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->makeStatus('Active', 'cycle');

        $land = Land::factory()->create(['farmer_id' => $owner->id]);
        $crop = Crop::factory()->create();
        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => Status::where('type', 'cycle')->where('name', 'Active')->value('id'),
            'end_date' => now()->addDays(1)->toDateString(),
        ]);

        Sanctum::actingAs($other);

        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_dashboard_summary_attention_count_is_correct(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $this->makeStatus('Active', 'cycle');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create();

        // (a) Panen urgent (H+1) → attention.
        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => Status::where('type', 'cycle')->where('name', 'Active')->value('id'),
            'end_date' => now()->addDays(1)->toDateString(),
        ]);
        // (b) Panen upcoming (H+6, dalam jangkauan 7 tapi > urgent window) → BUKAN attention.
        Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => Status::where('type', 'cycle')->where('name', 'Active')->value('id'),
            'end_date' => now()->addDays(6)->toDateString(),
        ]);

        // (c) Restock urgent → attention.
        $warehouse = Warehouse::factory()->create(['farmer_id' => $user->id]);
        $category = Categories::factory()->create();
        Items::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'stock' => 1,
        ]);

        Sanctum::actingAs($user);

        // 3 tindakan total, 2 di antaranya {urgent, urgent} → attention_count = 2.
        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(3, 'data');

        $this->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('attention_count', 2)
            ->assertJsonPath('task_count', 3);
    }
}
