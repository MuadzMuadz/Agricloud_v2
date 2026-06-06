<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Menguji alur forgot/reset password (Auth-ForgotReset-Backend, PESAN-BACKEND §13–14).
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    public function test_forgot_password_sends_reset_link_to_registered_email(): void
    {
        Notification::fake();
        $this->seedRoles();
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'ada@example.com'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_returns_200_even_for_unknown_email(): void
    {
        Notification::fake();
        $this->seedRoles();

        // Anti-enumeration: tetap 200 walau email tidak terdaftar.
        $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_reset_password_with_valid_token_changes_password(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertOk()->assertJsonStructure(['message']);

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }

    public function test_reset_password_with_invalid_token_returns_422(): void
    {
        $this->seedRoles();
        User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/auth/reset-password', [
            'token' => 'token-ngawur',
            'email' => 'reset@example.com',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertStatus(422);
    }
}
