<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Menguji kontrak login Google (Integration-GoogleOAuth, Opsi B / ID-token).
 * Socialite di-mock supaya tidak memanggil Google sungguhan.
 */
class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'farmer']);
    }

    /**
     * Bangun objek user Socialite palsu beserta klaim `aud` yang valid.
     */
    private function fakeGoogleUser(string $id, string $email, string $name = 'Budi Google'): SocialiteUser
    {
        $user = new SocialiteUser;
        $user->setRaw(['aud' => config('services.google.client_id')]);
        $user->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => 'https://lh3.googleusercontent.com/a/avatar',
        ]);

        return $user;
    }

    /**
     * Pasang mock Socialite: `valid-token` -> $user, token lain -> exception.
     */
    private function mockSocialite(SocialiteUser $user): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('userFromToken')
            ->with('valid-token')->andReturn($user);
        $provider->shouldReceive('userFromToken')
            ->withAnyArgs()->andThrow(new \RuntimeException('invalid token'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_google_login_creates_new_user_and_returns_token(): void
    {
        $this->seedRoles();
        $this->mockSocialite($this->fakeGoogleUser('google-sub-123', 'budi@gmail.com'));

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'token_type']])
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertDatabaseHas('users', [
            'email' => 'budi@gmail.com',
            'google_id' => 'google-sub-123',
            'provider' => 'google',
        ]);
    }

    public function test_google_login_links_existing_user_by_email_without_duplicating(): void
    {
        $this->seedRoles();
        $existing = User::factory()->create([
            'email' => 'budi@gmail.com',
            'google_id' => null,
            'provider' => null,
        ]);

        $this->mockSocialite($this->fakeGoogleUser('google-sub-123', 'budi@gmail.com'));

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer');

        // Tidak menduplikasi: tetap satu user dengan email tersebut, kini tertaut.
        $this->assertSame(1, User::where('email', 'budi@gmail.com')->count());
        $this->assertDatabaseHas('users', [
            'id' => $existing->id,
            'google_id' => 'google-sub-123',
            'provider' => 'google',
        ]);
    }

    public function test_google_login_is_idempotent_for_same_google_account(): void
    {
        $this->seedRoles();
        $this->mockSocialite($this->fakeGoogleUser('google-sub-123', 'budi@gmail.com'));

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();
        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();

        $this->assertSame(1, User::where('google_id', 'google-sub-123')->count());
    }

    public function test_google_login_rejects_invalid_id_token_with_401(): void
    {
        $this->seedRoles();
        $this->mockSocialite($this->fakeGoogleUser('google-sub-123', 'budi@gmail.com'));

        $this->postJson('/api/auth/google', ['id_token' => 'bogus-token'])
            ->assertStatus(401);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_login_requires_id_token(): void
    {
        $this->postJson('/api/auth/google', [])
            ->assertStatus(422);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
