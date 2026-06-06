<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Items;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji kontrak API notifikasi in-app (list/unread-count/read/read-all)
 * dan pemicu Observer Items -> LowStockNotification.
 */
class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    private function makeUserWithNotifications(int $count): User
    {
        $this->seedRoles();
        $user = User::factory()->create();

        for ($i = 0; $i < $count; $i++) {
            $user->notify(new RawDatabaseNotification([
                'type' => 'low_stock',
                'title' => 'Stok menipis',
                'body' => "Item ke-{$i} menipis",
                'data' => ['item_id' => $i, 'stock' => 5],
            ]));
        }

        return $user;
    }

    public function test_notifications_require_authentication(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }

    public function test_index_returns_paginated_notifications_in_contract_shape(): void
    {
        $user = $this->makeUserWithNotifications(2);
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'type', 'title', 'body', 'data', 'is_read', 'created_at']],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.type', 'low_stock')
            ->assertJsonPath('data.0.is_read', false);
    }

    public function test_unread_count_reflects_unread_notifications(): void
    {
        $user = $this->makeUserWithNotifications(3);
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 3);
    }

    public function test_read_marks_single_notification_and_decrements_count(): void
    {
        $user = $this->makeUserWithNotifications(2);
        Sanctum::actingAs($user);

        $id = $user->notifications()->first()->id;

        $this->postJson("/api/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true)
            ->assertJsonPath('data.id', $id);

        $this->getJson('/api/notifications/unread-count')
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_read_all_marks_everything_read(): void
    {
        $user = $this->makeUserWithNotifications(4);
        Sanctum::actingAs($user);

        $this->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->getJson('/api/notifications/unread-count')
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_read_returns_404_for_other_users_notification(): void
    {
        $owner = $this->makeUserWithNotifications(1);
        $other = User::factory()->create();
        $id = $owner->notifications()->first()->id;

        Sanctum::actingAs($other);

        $this->postJson("/api/notifications/{$id}/read")->assertNotFound();
    }

    public function test_low_stock_observer_notifies_warehouse_owner_when_below_threshold(): void
    {
        $this->seedRoles();
        $farmer = User::factory()->create();
        $warehouse = Warehouse::factory()->create(['farmer_id' => $farmer->id]);
        $category = Categories::factory()->create();

        Items::create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'name' => 'Pupuk Urea',
            'unit' => 'kg',
            'stock' => config('agricloud.low_stock_threshold') - 1,
        ]);

        $this->assertSame(1, $farmer->fresh()->notifications()->count());
        $this->assertSame('low_stock', $farmer->notifications()->first()->data['type']);
    }

    public function test_low_stock_observer_does_not_notify_when_stock_sufficient(): void
    {
        $this->seedRoles();
        $farmer = User::factory()->create();
        $warehouse = Warehouse::factory()->create(['farmer_id' => $farmer->id]);
        $category = Categories::factory()->create();

        Items::create([
            'warehouse_id' => $warehouse->id,
            'category_id' => $category->id,
            'name' => 'Pupuk Cukup',
            'unit' => 'kg',
            'stock' => config('agricloud.low_stock_threshold') + 50,
        ]);

        $this->assertSame(0, $farmer->fresh()->notifications()->count());
    }
}

/**
 * Notifikasi pembantu untuk uji endpoint: mengirim payload mentah ke channel
 * database tanpa bergantung pada salah satu class domain.
 */
class RawDatabaseNotification extends Notification
{
    /** @param array<string, mixed> $payload */
    public function __construct(private array $payload) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
