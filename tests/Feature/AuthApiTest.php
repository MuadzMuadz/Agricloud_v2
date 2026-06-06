<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Land;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji kontrak API yang dibutuhkan web frontend (auth, myfields, crop-templates).
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    public function test_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized(); // 401
    }

    public function test_user_endpoint_returns_authenticated_user(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonFragment(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $this->seedRoles();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Pak Tani',
            'email' => 'tani@example.com',
            'phone_number' => '0811111111',
            'password' => 'password123',
            'role' => 'farmer',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['access_token', 'token_type']])
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertDatabaseHas('users', [
            'email' => 'tani@example.com',
            'phone_number' => '0811111111',
        ]);
    }

    public function test_register_rejects_unknown_role(): void
    {
        $this->seedRoles();

        $this->postJson('/api/auth/register', [
            'name' => 'X',
            'email' => 'x@example.com',
            'phone_number' => '0812222222',
            'password' => 'password123',
            'role' => 'wizard',
        ])->assertStatus(422);
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['email' => 'login@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password', // default factory password
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'token_type']]);
    }

    public function test_login_with_remember_issues_long_lived_token(): void
    {
        $this->seedRoles();
        User::factory()->create(['email' => 'remember@example.com']);

        $expiresAt = $this->postJson('/api/auth/login', [
            'email' => 'remember@example.com',
            'password' => 'password',
            'remember' => true,
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_at']])
            ->json('data.expires_at');

        // TTL remember ≈ 30 hari (43200 menit).
        $minutes = now()->diffInMinutes($expiresAt);
        $this->assertGreaterThan(43200 - 5, $minutes);
        $this->assertLessThanOrEqual(43200 + 1, $minutes);
    }

    public function test_login_without_remember_issues_short_lived_token(): void
    {
        $this->seedRoles();
        User::factory()->create(['email' => 'default@example.com']);

        $expiresAt = $this->postJson('/api/auth/login', [
            'email' => 'default@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->json('data.expires_at');

        // TTL default ≈ 1 hari (1440 menit).
        $minutes = now()->diffInMinutes($expiresAt);
        $this->assertGreaterThan(1440 - 5, $minutes);
        $this->assertLessThanOrEqual(1440 + 1, $minutes);
    }

    public function test_register_issues_token_with_default_ttl(): void
    {
        $this->seedRoles();

        $expiresAt = $this->postJson('/api/auth/register', [
            'name' => 'TTL User',
            'email' => 'ttl@example.com',
            'phone_number' => '0813333333',
            'password' => 'password123',
            'role' => 'farmer',
        ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_at']])
            ->json('data.expires_at');

        $minutes = now()->diffInMinutes($expiresAt);
        $this->assertGreaterThan(1440 - 5, $minutes);
        $this->assertLessThanOrEqual(1440 + 1, $minutes);
    }

    public function test_expired_token_is_rejected_with_401(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $plainToken = $user->createToken('api', ['*'], now()->subMinute())->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->getJson('/api/auth/user')
            ->assertUnauthorized(); // 401
    }

    public function test_login_returns_422_with_invalid_credentials(): void
    {
        $this->seedRoles();
        User::factory()->create(['email' => 'login@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_auth_user_returns_profile_shape(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'email', 'email_verified_at', 'phone_number',
                    'role', 'profile_photo', 'created_at', 'updated_at', 'profile_photo_url',
                ],
            ])
            ->assertJsonPath('data.role', $user->Role->name);
    }

    public function test_myfields_index_returns_only_owner_lands(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $other = User::factory()->create();

        Land::factory()->count(2)->create(['farmer_id' => $user->id]);
        Land::factory()->create(['farmer_id' => $other->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/myfields')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'description', 'thumbnail', 'location' => ['latitude', 'longitude'], 'area', 'owner' => ['id', 'name']]],
            ]);
    }

    public function test_myfields_store_creates_land_with_thumbnail(): void
    {
        Storage::fake('public');
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/myfields', [
            'name' => 'Sawah A',
            'description' => 'Sawah uji',
            'area' => '120.5',
            'latitude' => '-6.2',
            'longitude' => '106.8',
            'thumbnail' => UploadedFile::fake()->create('field.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Sawah A')
            ->assertJsonPath('data.owner.id', $user->id);

        $this->assertDatabaseHas('lands', [
            'name' => 'Sawah A',
            'farmer_id' => $user->id,
        ]);
    }

    public function test_myfields_requires_authentication(): void
    {
        $this->getJson('/api/myfields')->assertUnauthorized();
    }

    public function test_crop_templates_are_public(): void
    {
        Crop::factory()->count(3)->create();

        $this->getJson('/api/crop-templates')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'description']]]);
    }
}
