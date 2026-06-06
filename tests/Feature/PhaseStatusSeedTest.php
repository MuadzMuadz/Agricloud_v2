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
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PhaseStatusFixSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji tiket [[Cycle-PhaseStatusSeed]]: fase selalu nyambung ke Status
 * `type='phase'`, tiap siklus aktif punya tepat satu fase Active, dan
 * `GET /api/cycles` mengembalikan `phase` terisi.
 */
class PhaseStatusSeedTest extends TestCase
{
    use RefreshDatabase;

    /** Status dibuat via factory agar kolom `type` ikut terisi. */
    private function makeStatus(string $name, string $type): Status
    {
        return Status::factory()->create(['name' => $name, 'type' => $type]);
    }

    /** Bangun satu siklus aktif dengan fase nyangkut ke Status salah type. */
    private function buildMislinkedActiveCycle(User $user, int $phaseCount = 3): array
    {
        $activeCycleStatus = $this->makeStatus('Active', 'cycle');
        $wrongStatus = $this->makeStatus('Completed', 'movement');

        $land = Land::factory()->create(['farmer_id' => $user->id]);
        $crop = Crop::factory()->create(['name' => 'Cabai Merah']);

        $stages = collect();
        for ($i = 1; $i <= $phaseCount; $i++) {
            $stages->push(Stage::factory()->create([
                'crop_id' => $crop->id,
                'name' => "Stage $i",
                'order' => $i,
            ]));
        }

        $cycle = Cycle::factory()->create([
            'land_id' => $land->id,
            'crop_id' => $crop->id,
            'status_id' => $activeCycleStatus->id,
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->addDays(40)->toDateString(),
        ]);

        $phases = $stages->map(fn (Stage $stage) => Phase::factory()->create([
            'cycle_id' => $cycle->id,
            'stage_id' => $stage->id,
            'status_id' => $wrongStatus->id,
        ]));

        return [$land, $cycle, $phases];
    }

    public function test_database_seed_links_all_phases_to_phase_type_status(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Tak ada satu pun fase nyangkut ke Status type != 'phase'.
        $stuck = Phase::with('Status')->get()
            ->filter(fn (Phase $p) => optional($p->Status)->type !== 'phase');
        $this->assertCount(0, $stuck, 'Masih ada fase nyangkut ke Status type != phase.');

        $cycles = Cycle::with(['Status', 'Phases.Status'])->get();
        $activeCycles = $cycles->filter(fn (Cycle $c) => optional($c->Status)->name === 'Active');
        $this->assertGreaterThan(0, $activeCycles->count(), 'Tidak ada siklus aktif hasil seed.');

        // Siklus aktif: tepat satu fase phase/Active.
        foreach ($activeCycles as $cycle) {
            $activePhases = $cycle->Phases->filter(
                fn (Phase $p) => optional($p->Status)->name === 'Active'
                    && optional($p->Status)->type === 'phase'
            );
            $this->assertCount(1, $activePhases, "Siklus #{$cycle->id} aktif harus punya tepat satu fase Active.");
        }

        // Siklus non-aktif: tidak boleh punya fase Active.
        foreach ($cycles->filter(fn (Cycle $c) => optional($c->Status)->name !== 'Active') as $cycle) {
            $activePhases = $cycle->Phases->filter(fn (Phase $p) => optional($p->Status)->name === 'Active');
            $this->assertCount(0, $activePhases, "Siklus #{$cycle->id} non-aktif tidak boleh punya fase Active.");
        }
    }

    public function test_fix_seeder_remaps_mislinked_phases_and_preserves_needs(): void
    {
        Role::create(['name' => 'farmer']);
        $user = User::factory()->create();
        [, $cycle, $phases] = $this->buildMislinkedActiveCycle($user);

        // Pasang Needs pada salah satu fase untuk membuktikan tidak terhapus.
        $warehouse = Warehouse::factory()->create();
        $category = Categories::factory()->create();
        $item = Items::factory()->create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
        ]);
        $need = Needs::factory()->create([
            'phase_id' => $phases->first()->id,
            'item_id' => $item->id,
        ]);

        $this->seed(PhaseStatusFixSeeder::class);

        $cycle->load('Phases.Status');

        // Tidak ada lagi fase nyangkut ke type != 'phase'.
        $stuck = $cycle->Phases->filter(fn (Phase $p) => optional($p->Status)->type !== 'phase');
        $this->assertCount(0, $stuck);

        // Tepat satu fase Active.
        $active = $cycle->Phases->filter(fn (Phase $p) => optional($p->Status)->name === 'Active');
        $this->assertCount(1, $active);

        // Needs tetap ada (remap, bukan delete).
        $this->assertDatabaseHas('needs', ['id' => $need->id]);
        $this->assertEquals(3, $cycle->Phases->count(), 'Jumlah fase tidak boleh berubah saat remap.');
    }

    public function test_fix_seeder_is_idempotent(): void
    {
        $user = User::factory()->create();
        [, $cycle] = $this->buildMislinkedActiveCycle($user);

        $this->seed(PhaseStatusFixSeeder::class);
        $phaseActiveStatusId = Status::where('type', 'phase')->where('name', 'Active')->value('id');
        $firstActiveId = Phase::where('cycle_id', $cycle->id)
            ->where('status_id', $phaseActiveStatusId)
            ->value('id');

        $this->seed(PhaseStatusFixSeeder::class);

        $active = Phase::where('cycle_id', $cycle->id)->get()
            ->filter(fn (Phase $p) => optional($p->Status)->name === 'Active'
                && optional($p->Status)->type === 'phase');

        $this->assertCount(1, $active, 'Setelah dijalankan dua kali tetap tepat satu Active.');
        $this->assertEquals($firstActiveId, $active->first()->id, 'Fase Active harus sama (idempoten).');
    }

    public function test_cycles_endpoint_returns_phase_after_fix(): void
    {
        Role::create(['name' => 'farmer']);
        $user = User::factory()->create();
        [$land, $cycle] = $this->buildMislinkedActiveCycle($user);

        $this->seed(PhaseStatusFixSeeder::class);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/cycles?field_id='.$land->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertNotNull($response->json('data.0.phase'), 'phase siklus aktif tidak boleh null.');
        $this->assertStringStartsWith('Stage', $response->json('data.0.phase'));
    }
}
