<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Proxy data cuaca via Open-Meteo (Integration-Weather §10–11).
 *
 * Tugas:
 * - Resolusi lokasi user (rantai: device -> manual -> lahan -> IP -> default).
 * - Ambil cuaca (current + hourly + variabel pertanian), normalisasi jadi 1 bentuk.
 * - Cache per lokasi agar hemat call.
 * - Geocoding autocomplete kecamatan.
 */
class WeatherController extends Controller
{
    /** Jumlah jam prakiraan yang dikembalikan pada strip `hourly`. */
    private const HOURLY_HOURS = 12;

    /**
     * GET /api/weather?lat=&lon=
     *
     * Bila lat/lon dikirim FE (lokasi perangkat/GPS) -> dipakai langsung.
     * Bila kosong -> backend resolusi via setting/lahan/IP/default.
     * Route publik, tapi Bearer opsional: bila token ada, resolusi ikut
     * setting & lahan user.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lon' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $location = $this->resolveLocation(
            $request,
            isset($validated['lat']) ? (float) $validated['lat'] : null,
            isset($validated['lon']) ? (float) $validated['lon'] : null,
        );

        $ttl = (int) config('services.weather.cache_ttl', 1800);
        $cacheKey = sprintf('weather:%.4f:%.4f', $location['lat'], $location['lon']);

        $weather = Cache::remember(
            $cacheKey,
            $ttl,
            fn () => $this->fetchWeather($location['lat'], $location['lon']),
        );

        if ($weather === null) {
            return response()->json(['message' => 'Gagal mengambil data cuaca.'], 502);
        }

        return response()->json([
            'data' => array_merge(['location' => $location], $weather),
        ]);
    }

    /**
     * GET /api/weather/search?q=Cirebon
     *
     * Autocomplete kecamatan via Open-Meteo Geocoding (proxy, tanpa key).
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        try {
            $response = Http::timeout(8)->get(
                config('services.weather.geocoding_url').'/search',
                [
                    'name' => $validated['q'],
                    'count' => 8,
                    'language' => 'id',
                    'format' => 'json',
                ],
            );
        } catch (\Throwable $e) {
            return response()->json(['data' => []]);
        }

        if (! $response->successful()) {
            return response()->json(['data' => []]);
        }

        $results = $response->json('results') ?? [];

        $data = array_map(fn (array $r): array => [
            'id' => $r['id'] ?? null,
            'name' => $r['name'] ?? null,
            'district' => $this->composeDistrict($r),
            'admin1' => $r['admin1'] ?? null,
            'admin2' => $r['admin2'] ?? null,
            'country' => $r['country'] ?? null,
            'lat' => isset($r['latitude']) ? (float) $r['latitude'] : null,
            'lon' => isset($r['longitude']) ? (float) $r['longitude'] : null,
        ], $results);

        return response()->json(['data' => $data]);
    }

    /**
     * Rantai resolusi lokasi: device (GPS) -> manual -> lahan utama -> IP -> default.
     *
     * @return array{name: string, lat: float, lon: float, source: string}
     */
    private function resolveLocation(Request $request, ?float $lat, ?float $lon): array
    {
        // 1. Lokasi perangkat (GPS) — koordinat dikirim FE.
        if ($lat !== null && $lon !== null) {
            return [
                'name' => 'Lokasi perangkat',
                'lat' => $lat,
                'lon' => $lon,
                'source' => 'device',
            ];
        }

        // Bearer opsional: ambil user dari token bila ada (route publik).
        $user = $request->user() ?? auth('sanctum')->user();

        // 2. Setelan manual (Settings) — konsisten lintas perangkat.
        if ($user
            && $user->weather_mode === 'manual'
            && $user->weather_lat !== null
            && $user->weather_lon !== null
        ) {
            return [
                'name' => $user->weather_district ?? 'Lokasi tersimpan',
                'lat' => (float) $user->weather_lat,
                'lon' => (float) $user->weather_lon,
                'source' => 'manual',
            ];
        }

        // 3. Lokasi lahan utama (fallback).
        if ($user) {
            $land = $user->Lands()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->latest()
                ->first();

            if ($land) {
                return [
                    'name' => $land->name ?? 'Lahan utama',
                    'lat' => (float) $land->latitude,
                    'lon' => (float) $land->longitude,
                    'source' => 'field',
                ];
            }
        }

        // 4. Geolokasi IP (server-side, coarse, tanpa izin).
        $ip = $this->ipLocation($request->ip());
        if ($ip !== null) {
            return $ip;
        }

        // 5. Default aman (Kejaksan, Cirebon) — widget tak pernah kosong.
        return [
            'name' => (string) config('services.weather.default_district'),
            'lat' => (float) config('services.weather.default_lat'),
            'lon' => (float) config('services.weather.default_lon'),
            'source' => 'default',
        ];
    }

    /**
     * Ambil & normalisasi cuaca dari Open-Meteo. Null bila gagal.
     *
     * @return array<string, mixed>|null
     */
    private function fetchWeather(float $lat, float $lon): ?array
    {
        try {
            $response = Http::timeout(8)->get(
                config('services.weather.base_url').'/forecast',
                [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,is_day,weather_code,precipitation,cloud_cover,wind_speed_10m,wind_direction_10m',
                    'hourly' => 'temperature_2m,weather_code,is_day,precipitation_probability,'
                        .'et0_fao_evapotranspiration,evapotranspiration,vapour_pressure_deficit,'
                        .'soil_temperature_0cm,soil_temperature_6cm,soil_temperature_18cm,'
                        .'soil_moisture_0_to_1cm,soil_moisture_1_to_3cm,soil_moisture_3_to_9cm,'
                        .'soil_moisture_9_to_27cm,soil_moisture_27_to_81cm',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,sunrise,sunset,'
                        .'uv_index_max,precipitation_probability_max,precipitation_sum,et0_fao_evapotranspiration',
                    'timezone' => 'auto',
                    'forecast_days' => 3,
                ],
            );
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->normalize($response->json() ?? []);
    }

    /**
     * Ubah payload mentah Open-Meteo jadi kontrak §5.
     *
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function normalize(array $json): array
    {
        $current = $json['current'] ?? [];
        $hourly = $json['hourly'] ?? [];
        $daily = $json['daily'] ?? [];
        $times = $hourly['time'] ?? [];

        // Index jam saat ini di array hourly (untuk rain_prob & variabel pertanian).
        // `current.time` presisi menit (mis. ...T13:45), `hourly.time` on-the-hour
        // (...T13:00) -> cocokkan per-jam (prefix "YYYY-MM-DDTHH"), bukan string penuh.
        $idx = 0;
        if (! empty($current['time']) && ! empty($times)) {
            $currentHour = substr((string) $current['time'], 0, 13);
            foreach ($times as $i => $t) {
                if (substr((string) $t, 0, 13) === $currentHour) {
                    $idx = $i;
                    break;
                }
            }
        }

        $code = (int) ($current['weather_code'] ?? ($hourly['weather_code'][$idx] ?? 0));

        // Strip prakiraan jam-an: 12 jam ke depan mulai dari jam sekarang.
        $forecast = [];
        $count = count($times);
        for ($i = $idx; $i < min($idx + self::HOURLY_HOURS, $count); $i++) {
            $forecast[] = [
                'time' => substr((string) $times[$i], 11, 5), // HH:MM
                'temp_c' => $this->numOrNull($hourly['temperature_2m'][$i] ?? null),
                'weather_code' => (int) ($hourly['weather_code'][$i] ?? 0),
                'is_day' => isset($hourly['is_day'][$i]) ? (bool) $hourly['is_day'][$i] : null,
                'rain_prob' => $this->numOrNull($hourly['precipitation_probability'][$i] ?? null),
            ];
        }

        // Prakiraan harian (sesuai forecast_days): ringkasan + matahari terbit/terbenam.
        $forecastDaily = [];
        $dCount = count($daily['time'] ?? []);
        for ($i = 0; $i < $dCount; $i++) {
            $forecastDaily[] = [
                'date' => $daily['time'][$i] ?? null,
                'weather_code' => (int) ($daily['weather_code'][$i] ?? 0),
                'condition' => $this->codeToCondition((int) ($daily['weather_code'][$i] ?? 0)),
                'temp_max_c' => $this->numOrNull($daily['temperature_2m_max'][$i] ?? null),
                'temp_min_c' => $this->numOrNull($daily['temperature_2m_min'][$i] ?? null),
                'sunrise' => $daily['sunrise'][$i] ?? null,
                'sunset' => $daily['sunset'][$i] ?? null,
                'uv_index_max' => $this->numOrNull($daily['uv_index_max'][$i] ?? null),
                'rain_prob_max' => $this->numOrNull($daily['precipitation_probability_max'][$i] ?? null),
                'precip_sum_mm' => $this->numOrNull($daily['precipitation_sum'][$i] ?? null),
                'et0_mm' => $this->numOrNull($daily['et0_fao_evapotranspiration'][$i] ?? null),
            ];
        }

        return [
            'current' => [
                'temp_c' => $this->numOrNull($current['temperature_2m'] ?? null),
                'feels_like_c' => $this->numOrNull($current['apparent_temperature'] ?? null),
                'condition' => $this->codeToCondition($code),
                'weather_code' => $code,
                'is_day' => isset($current['is_day']) ? (bool) $current['is_day'] : null,
                'humidity' => $this->numOrNull($current['relative_humidity_2m'] ?? null),
                'wind_kph' => $this->numOrNull($current['wind_speed_10m'] ?? null),
                'wind_deg' => $this->numOrNull($current['wind_direction_10m'] ?? null),
                'rain_prob' => $this->numOrNull($hourly['precipitation_probability'][$idx] ?? null),
                'precip_mm' => $this->numOrNull($current['precipitation'] ?? null),
                'cloud_cover' => $this->numOrNull($current['cloud_cover'] ?? null),
            ],
            // Variabel pertanian (current hour). Kelembapan tanah per lapisan akar
            // = sinyal utama keputusan siram; VPD = stres air tanaman.
            'agri' => [
                'et0_mm' => $this->numOrNull($hourly['et0_fao_evapotranspiration'][$idx] ?? null),
                'evapotranspiration_mm' => $this->numOrNull($hourly['evapotranspiration'][$idx] ?? null),
                'vpd_kpa' => $this->numOrNull($hourly['vapour_pressure_deficit'][$idx] ?? null),
                'soil_temp_0cm' => $this->numOrNull($hourly['soil_temperature_0cm'][$idx] ?? null),
                'soil_temp_6cm' => $this->numOrNull($hourly['soil_temperature_6cm'][$idx] ?? null),
                'soil_temp_18cm' => $this->numOrNull($hourly['soil_temperature_18cm'][$idx] ?? null),
                'soil_moisture_0_1cm' => $this->numOrNull($hourly['soil_moisture_0_to_1cm'][$idx] ?? null),
                'soil_moisture_1_3cm' => $this->numOrNull($hourly['soil_moisture_1_to_3cm'][$idx] ?? null),
                'soil_moisture_3_9cm' => $this->numOrNull($hourly['soil_moisture_3_to_9cm'][$idx] ?? null),
                'soil_moisture_9_27cm' => $this->numOrNull($hourly['soil_moisture_9_to_27cm'][$idx] ?? null),
                'soil_moisture_27_81cm' => $this->numOrNull($hourly['soil_moisture_27_to_81cm'][$idx] ?? null),
            ],
            'hourly' => $forecast,
            'daily' => $forecastDaily,
            // Selaraskan tz dgn waktu lokal data (hourly/current pakai timezone=auto),
            // bukan UTC app — supaya FE tak salah 7 jam saat memetakan ke strip jam.
            'fetched_at' => now()->utcOffset((int) ($json['utc_offset_seconds'] ?? 0) / 60)->toIso8601String(),
        ];
    }

    /**
     * Geolokasi via IP (server-side). Null untuk IP privat/loopback atau gagal.
     *
     * @return array{name: string, lat: float, lon: float, source: string}|null
     */
    private function ipLocation(?string $ip): ?array
    {
        if ($ip === null
            || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        ) {
            return null;
        }

        try {
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,city,regionName,lat,lon',
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                $name = trim(($response->json('city') ?? '').', '.($response->json('regionName') ?? ''), ', ');

                return [
                    'name' => $name !== '' ? $name : 'Lokasi IP',
                    'lat' => (float) $response->json('lat'),
                    'lon' => (float) $response->json('lon'),
                    'source' => 'ip',
                ];
            }
        } catch (\Throwable $e) {
            // diam-diam mundur ke default
        }

        return null;
    }

    /**
     * Gabung nama + provinsi untuk label kecamatan ("Kejaksan, Jawa Barat").
     *
     * @param  array<string, mixed>  $r
     */
    private function composeDistrict(array $r): string
    {
        return trim(($r['name'] ?? '').(! empty($r['admin1']) ? ', '.$r['admin1'] : ''), ', ');
    }

    private function numOrNull(mixed $value): int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }

    /**
     * WMO weather code -> label kondisi (Bahasa Indonesia).
     */
    private function codeToCondition(int $code): string
    {
        return match (true) {
            $code === 0 => 'Cerah',
            $code === 1 => 'Cerah berawan',
            $code === 2 => 'Berawan sebagian',
            $code === 3 => 'Berawan',
            in_array($code, [45, 48], true) => 'Berkabut',
            in_array($code, [51, 53, 55], true) => 'Gerimis',
            in_array($code, [56, 57], true) => 'Gerimis beku',
            in_array($code, [61, 63, 65], true) => 'Hujan',
            in_array($code, [66, 67], true) => 'Hujan beku',
            in_array($code, [71, 73, 75], true) => 'Salju',
            $code === 77 => 'Butiran salju',
            in_array($code, [80, 81, 82], true) => 'Hujan lokal',
            in_array($code, [85, 86], true) => 'Hujan salju',
            $code === 95 => 'Badai petir',
            in_array($code, [96, 99], true) => 'Badai petir disertai es',
            default => 'Tidak diketahui',
        };
    }
}
