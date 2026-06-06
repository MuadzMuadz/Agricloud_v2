<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji kontrak Settings-Backend: preferensi user (PATCH /auth/settings)
 * & ganti password (POST /auth/change-password).
 */
class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    public function test_update_settings_saves_and_returns_object(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/settings', [
            'theme' => 'dark',
            'language' => 'id',
            'notifications' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.theme', 'dark')
            ->assertJsonPath('data.language', 'id')
            ->assertJsonPath('data.notifications', false);

        $this->assertSame(
            ['theme' => 'dark', 'language' => 'id', 'notifications' => false],
            $user->fresh()->settings,
        );
    }

    public function test_update_settings_merges_partial_update(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['settings' => ['theme' => 'light', 'language' => 'en']]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/settings', ['theme' => 'dark'])
            ->assertOk()
            ->assertJsonPath('data.theme', 'dark')
            ->assertJsonPath('data.language', 'en'); // tetap dari settings lama
    }

    public function test_settings_appear_in_auth_user(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['settings' => ['theme' => 'dark', 'language' => 'id', 'notifications' => true]]);
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('data.settings.theme', 'dark')
            ->assertJsonPath('data.settings.notifications', true);
    }

    public function test_update_settings_rejects_invalid_values(): void
    {
        $this->seedRoles();
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/auth/settings', ['theme' => 'pink'])->assertStatus(422);
        $this->patchJson('/api/auth/settings', ['language' => 'fr'])->assertStatus(422);
        $this->patchJson('/api/auth/settings', ['notifications' => 'maybe'])->assertStatus(422);
    }

    public function test_update_settings_requires_authentication(): void
    {
        $this->patchJson('/api/auth/settings', ['theme' => 'dark'])->assertUnauthorized();
    }

    public function test_change_password_succeeds_and_allows_login_with_new_password(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['email' => 'ganti@example.com']); // factory password: 'password'
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/change-password', [
            'current_password' => 'password',
            'password' => 'rahasiabaru123',
            'password_confirmation' => 'rahasiabaru123',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Kata sandi berhasil diperbarui.');

        $this->postJson('/api/auth/login', [
            'email' => 'ganti@example.com',
            'password' => 'rahasiabaru123',
        ])->assertOk();
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $this->seedRoles();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/auth/change-password', [
            'current_password' => 'salahbanget',
            'password' => 'rahasiabaru123',
            'password_confirmation' => 'rahasiabaru123',
        ])->assertStatus(422);
    }

    public function test_change_password_requires_confirmation_and_min_length(): void
    {
        $this->seedRoles();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/auth/change-password', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422);

        $this->postJson('/api/auth/change-password', [
            'current_password' => 'password',
            'password' => 'rahasiabaru123',
            'password_confirmation' => 'beda12345',
        ])->assertStatus(422);
    }

    public function test_change_password_requires_authentication(): void
    {
        $this->postJson('/api/auth/change-password', [
            'current_password' => 'password',
            'password' => 'rahasiabaru123',
            'password_confirmation' => 'rahasiabaru123',
        ])->assertUnauthorized();
    }
}
