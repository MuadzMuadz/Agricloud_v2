<?php

namespace Tests\Feature;

use App\Models\Land;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji endpoint cuaca (Integration-Weather): proxy Open-Meteo, resolusi
 * lokasi (device -> manual -> lahan -> IP -> default), geocoding, & simpan setting.
 */
class WeatherApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'farmer']);
    }

    /** Payload tiruan Open-Meteo forecast. */
    private function fakeForecast(): array
    {
        return [
            'utc_offset_seconds' => 25200, // WIB (+07:00)
            'current' => [
                'time' => '2026-06-06T07:30', // presisi menit (seperti Open-Meteo asli)
                'temperature_2m' => 31.2,
                'apparent_temperature' => 35.7,
                'relative_humidity_2m' => 68,
                'is_day' => 1,
                'weather_code' => 2,
                'precipitation' => 0.0,
                'cloud_cover' => 49,
                'wind_speed_10m' => 12.4,
                'wind_direction_10m' => 71,
            ],
            'hourly' => [
                'time' => ['2026-06-06T07:00', '2026-06-06T08:00', '2026-06-06T09:00'],
                'temperature_2m' => [31.2, 32.0, 33.1],
                'weather_code' => [2, 1, 1],
                'is_day' => [1, 1, 1],
                'precipitation_probability' => [40, 30, 10],
                'et0_fao_evapotranspiration' => [4.2, 4.5, 4.8],
                'evapotranspiration' => [0.01, 0.02, 0.03],
                'vapour_pressure_deficit' => [0.64, 0.70, 0.81],
                'soil_temperature_0cm' => [28.4, 29.1, 30.2],
                'soil_temperature_6cm' => [27.0, 27.3, 27.8],
                'soil_temperature_18cm' => [28.5, 28.5, 28.6],
                'soil_moisture_0_to_1cm' => [0.31, 0.30, 0.29],
                'soil_moisture_1_to_3cm' => [0.32, 0.31, 0.30],
                'soil_moisture_3_to_9cm' => [0.34, 0.33, 0.33],
                'soil_moisture_9_to_27cm' => [0.36, 0.36, 0.35],
                'soil_moisture_27_to_81cm' => [0.40, 0.40, 0.40],
            ],
            'daily' => [
                'time' => ['2026-06-06', '2026-06-07', '2026-06-08'],
                'weather_code' => [3, 2, 61],
                'temperature_2m_max' => [31.7, 32.5, 30.1],
                'temperature_2m_min' => [25.2, 26.4, 24.4],
                'sunrise' => ['2026-06-06T05:52', '2026-06-07T05:52', '2026-06-08T05:52'],
                'sunset' => ['2026-06-06T17:36', '2026-06-07T17:37', '2026-06-08T17:37'],
                'uv_index_max' => [7.25, 7.4, 6.8],
                'precipitation_probability_max' => [12, 6, 70],
                'precipitation_sum' => [0.0, 0.0, 5.4],
                'et0_fao_evapotranspiration' => [4.74, 5.23, 3.9],
            ],
        ];
    }

    private function fakeHttp(): void
    {
        Http::fake([
            'https://api.open-meteo.com/*' => Http::response($this->fakeForecast(), 200),
            'https://geocoding-api.open-meteo.com/*' => Http::response([
                'results' => [[
                    'id' => 1,
                    'name' => 'Cirebon',
                    'latitude' => -6.7063,
                    'longitude' => 108.5571,
                    'admin1' => 'Jawa Barat',
                    'admin2' => 'Kota Cirebon',
                    'country' => 'Indonesia',
                ]],
            ], 200),
            'ip-api.com/*' => Http::response(['status' => 'fail'], 200),
        ]);
    }

    public function test_weather_with_coordinates_returns_normalized_shape(): void
    {
        $this->fakeHttp();

        $this->getJson('/api/weather?lat=-6.7&lon=108.55')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'location' => ['name', 'lat', 'lon', 'source'],
                    'current' => [
                        'temp_c', 'feels_like_c', 'condition', 'weather_code', 'is_day',
                        'humidity', 'wind_kph', 'wind_deg', 'rain_prob', 'precip_mm', 'cloud_cover',
                    ],
                    'agri' => [
                        'et0_mm', 'evapotranspiration_mm', 'vpd_kpa',
                        'soil_temp_0cm', 'soil_temp_6cm', 'soil_temp_18cm',
                        'soil_moisture_0_1cm', 'soil_moisture_1_3cm', 'soil_moisture_3_9cm',
                        'soil_moisture_9_27cm', 'soil_moisture_27_81cm',
                    ],
                    'hourly' => [['time', 'temp_c', 'weather_code', 'is_day', 'rain_prob']],
                    'daily' => [[
                        'date', 'weather_code', 'condition', 'temp_max_c', 'temp_min_c',
                        'sunrise', 'sunset', 'uv_index_max', 'rain_prob_max', 'precip_sum_mm', 'et0_mm',
                    ]],
                    'fetched_at',
                ],
            ])
            ->assertJsonPath('data.location.source', 'device')
            ->assertJsonPath('data.current.weather_code', 2)
            ->assertJsonPath('data.current.condition', 'Berawan sebagian')
            ->assertJsonPath('data.current.feels_like_c', 35.7)
            ->assertJsonPath('data.current.is_day', true)
            ->assertJsonPath('data.current.rain_prob', 40)
            ->assertJsonPath('data.agri.vpd_kpa', 0.64)
            ->assertJsonPath('data.agri.soil_moisture_9_27cm', 0.36)
            ->assertJsonPath('data.hourly.0.time', '07:00')
            ->assertJsonCount(3, 'data.daily')
            ->assertJsonPath('data.daily.2.condition', 'Hujan')
            ->assertJsonPath('data.daily.0.sunrise', '2026-06-06T05:52');

        // fetched_at harus selaras tz data (+07:00), bukan UTC app.
        $this->assertStringEndsWith('+07:00', $this->getJson('/api/weather?lat=-6.7&lon=108.55')->json('data.fetched_at'));
    }

    public function test_weather_uses_manual_setting_when_no_coordinates(): void
    {
        $this->fakeHttp();
        $this->seedRoles();

        $user = User::factory()->create([
            'weather_mode' => 'manual',
            'weather_district' => 'Kejaksan, Jawa Barat',
            'weather_lat' => -6.7063,
            'weather_lon' => 108.5571,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.location.source', 'manual')
            ->assertJsonPath('data.location.name', 'Kejaksan, Jawa Barat');
    }

    public function test_weather_falls_back_to_primary_land(): void
    {
        $this->fakeHttp();
        $this->seedRoles();

        $user = User::factory()->create();
        Land::factory()->create([
            'farmer_id' => $user->id,
            'name' => 'Sawah Utama',
            'latitude' => -6.9,
            'longitude' => 107.6,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.location.source', 'field')
            ->assertJsonPath('data.location.name', 'Sawah Utama');
    }

    public function test_weather_falls_back_to_default_without_auth(): void
    {
        $this->fakeHttp();

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.location.source', 'default')
            ->assertJsonPath('data.location.name', 'Kejaksan, Cirebon');
    }

    public function test_weather_returns_502_when_provider_fails(): void
    {
        Http::fake([
            'https://api.open-meteo.com/*' => Http::response('upstream down', 503),
            'ip-api.com/*' => Http::response(['status' => 'fail'], 200),
        ]);

        $this->getJson('/api/weather?lat=-6.7&lon=108.55')
            ->assertStatus(502)
            ->assertJsonPath('message', 'Gagal mengambil data cuaca.');
    }

    public function test_weather_search_maps_geocoding_results(): void
    {
        $this->fakeHttp();

        $this->getJson('/api/weather/search?q=Cirebon')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'district', 'admin1', 'admin2', 'country', 'lat', 'lon']],
            ])
            ->assertJsonPath('data.0.name', 'Cirebon')
            ->assertJsonPath('data.0.district', 'Cirebon, Jawa Barat')
            ->assertJsonPath('data.0.lat', -6.7063);
    }

    public function test_weather_search_requires_query(): void
    {
        $this->getJson('/api/weather/search')->assertStatus(422);
    }

    public function test_update_auth_user_saves_weather_settings(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/auth/user', [
            'weather_mode' => 'manual',
            'weather_district' => 'Kejaksan, Jawa Barat',
            'weather_lat' => -6.7063,
            'weather_lon' => 108.5571,
        ])
            ->assertOk()
            ->assertJsonPath('data.weather_mode', 'manual')
            ->assertJsonPath('data.weather_district', 'Kejaksan, Jawa Barat');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'weather_mode' => 'manual',
            'weather_district' => 'Kejaksan, Jawa Barat',
        ]);
    }

    public function test_update_auth_user_rejects_invalid_mode(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/auth/user', ['weather_mode' => 'satellite'])
            ->assertStatus(422);
    }

    public function test_update_auth_user_requires_authentication(): void
    {
        $this->putJson('/api/auth/user', ['weather_mode' => 'manual'])
            ->assertUnauthorized();
    }

    public function test_auth_user_includes_weather_fields(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['weather_mode', 'weather_district', 'weather_lat', 'weather_lon'],
            ]);
    }
}
