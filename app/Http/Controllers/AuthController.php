<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    /**
     * Menerbitkan Bearer token Sanctum dengan TTL sesuai mode sesi.
     *
     * `remember = true` memakai TTL panjang (config `agricloud.token_ttl.remember`),
     * selain itu TTL default. Token yang melewati `expires_at` ditolak guard (401).
     * Mengembalikan blok `data` siap-pakai (token + tipe + waktu kadaluarsa ISO-8601).
     *
     * @return array{access_token: string, token_type: string, expires_at: ?string}
     */
    private function issueToken(User $user, bool $remember = false): array
    {
        $minutes = (int) config($remember ? 'agricloud.token_ttl.remember' : 'agricloud.token_ttl.default');
        $token = $user->createToken('api', ['*'], now()->addMinutes($minutes));

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($token->accessToken->expires_at)->toIso8601String(),
        ];
    }

    /**
     * POST /api/auth/register
     *
     * Membuat user baru lalu mengembalikan Bearer token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:30', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string'],
        ]);

        // FE mengirim role sebagai nama (mis. "farmer"); DB menyimpan role_id.
        $roleName = $validated['role'] ?? 'farmer';
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role' => ["Role '{$roleName}' tidak tersedia."],
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => $validated['password'], // di-hash via cast 'hashed'
            'role_id' => $role->id,
        ]);

        return response()->json([
            'data' => $this->issueToken($user),
        ], 201);
    }

    /**
     * POST /api/auth/login
     *
     * Memvalidasi kredensial lalu mengembalikan Bearer token.
     * Kredensial salah -> HTTP 422 (ValidationException).
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        return response()->json([
            'data' => $this->issueToken($user, $request->boolean('remember')),
        ]);
    }

    /**
     * POST /api/auth/google  (publik)
     *
     * Login/registrasi via Google (Integration-GoogleOAuth, Opsi B / ID-token).
     * FE mengirim `id_token` dari Google Identity Services; backend memverifikasi
     * token ke Google lewat Socialite (stateless), memvalidasi `aud` = Client ID
     * kita, lalu mencari/membuat user (by google_id -> email) dan mengembalikan
     * Bearer token yang seragam dengan login biasa.
     *
     * `id_token` invalid / gagal verifikasi -> HTTP 401.
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        // Verifikasi token ke Google. Token invalid -> Socialite melempar -> 401.
        try {
            $googleUser = Socialite::driver('google')->stateless()
                ->userFromToken($validated['id_token']);
        } catch (Throwable) {
            abort(401, 'Token Google tidak valid.');
        }

        // Validasi audience: token harus diterbitkan untuk Client ID kita.
        // (Bila Google tidak menyertakan klaim `aud` pada response, lewati —
        // token sudah diverifikasi keabsahannya oleh Google di atas.)
        $clientId = config('services.google.client_id');
        $aud = $googleUser->user['aud'] ?? null;
        if ($aud !== null && $clientId !== null && ! hash_equals((string) $clientId, (string) $aud)) {
            abort(401, 'Token Google bukan untuk aplikasi ini.');
        }

        if (! $googleUser->getId() || ! $googleUser->getEmail()) {
            abort(401, 'Profil Google tidak lengkap.');
        }

        $user = $this->findOrCreateGoogleUser($googleUser);

        return response()->json([
            'data' => $this->issueToken($user),
        ]);
    }

    /**
     * Cari user berdasarkan google_id, lalu fallback ke email (tautkan akun
     * lama), atau buat user baru bila belum ada. Tidak menduplikasi akun:
     * `email` unique menjaga satu user per email.
     */
    private function findOrCreateGoogleUser($googleUser): User
    {
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Tautkan / segarkan info provider pada akun yang sudah ada.
            $user->forceFill([
                'google_id' => $user->google_id ?? $googleUser->getId(),
                'provider' => $user->provider ?? 'google',
                'profil_url' => $user->profil_url ?: $googleUser->getAvatar(),
            ])->save();

            return $user;
        }

        $role = Role::where('name', 'farmer')->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role' => ["Role 'farmer' tidak tersedia."],
            ]);
        }

        return User::create([
            'name' => $googleUser->getName() ?: $googleUser->getEmail(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'provider' => 'google',
            'profil_url' => $googleUser->getAvatar(),
            'role_id' => $role->id,
            'password' => null, // user sosial tidak punya password
        ]);
    }

    /**
     * GET /api/auth/user  (Bearer)
     *
     * Mengembalikan profil user yang sedang login.
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user()->load('Role'));
    }

    /**
     * PUT /api/auth/user  (Bearer)
     *
     * Menyimpan setelan lokasi cuaca user (Integration-Weather).
     * Hanya menyentuh kolom `weather_*` — profil lain tak diubah di sini.
     */
    public function update(Request $request): UserResource
    {
        $validated = $request->validate([
            'weather_mode' => ['nullable', 'string', 'in:manual,device'],
            'weather_district' => ['nullable', 'string', 'max:255'],
            'weather_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'weather_lon' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $user = $request->user();
        $user->fill($validated)->save();

        return new UserResource($user->load('Role'));
    }

    /**
     * PATCH /api/auth/settings  (Bearer)
     *
     * Menyimpan preferensi user (Settings-Backend §7). Semua field opsional;
     * partial update aman karena di-merge dengan settings yang sudah ada.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['sometimes', 'in:light,dark'],
            'language' => ['sometimes', 'in:id,en'],
            'notifications' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $user->settings = array_merge($user->settings ?? [], $validated);
        $user->save();

        return response()->json(['data' => $user->settings]);
    }

    /**
     * POST /api/auth/change-password  (Bearer)
     *
     * Ganti password untuk user yang sedang login (Settings-Backend §16).
     * `current_password` salah -> HTTP 422 (rule bawaan Laravel).
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => $request->password]); // di-hash via cast 'hashed'

        return response()->json(['message' => 'Kata sandi berhasil diperbarui.']);
    }

    /**
     * POST /api/auth/forgot-password  (publik)
     *
     * Mengirim email berisi link reset password ke FE.
     * Selalu balikan 200 (anti-enumeration) — tak membocorkan email mana yang terdaftar.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Jika email terdaftar, link reset sudah dikirim.',
        ]);
    }

    /**
     * POST /api/auth/reset-password  (publik)
     *
     * Menyetel password baru memakai token + email dari link reset.
     * Token invalid/kadaluarsa -> HTTP 422.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save(); // di-hash via cast 'hashed'
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Token reset tidak valid atau kadaluarsa.'],
            ]);
        }

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    /**
     * POST /api/auth/logout  (Bearer)
     *
     * Mencabut token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
