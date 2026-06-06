<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Memetakan kolom DB ke kontrak yang dibutuhkan FE web:
     * - `role`            -> nama role dari relasi (DB simpan `role_id`).
     * - `profile_photo`   -> kolom `profil_url`.
     * - `profile_photo_url` -> URL absolut dari `profil_url`.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'phone_number' => $this->phone_number,
            'role' => $this->whenLoaded('Role', fn () => $this->Role->name, $this->Role?->name),
            'profile_photo' => $this->profil_url,
            'settings' => $this->settings,
            'weather_mode' => $this->weather_mode,
            'weather_district' => $this->weather_district,
            'weather_lat' => $this->weather_lat,
            'weather_lon' => $this->weather_lon,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'profile_photo_url' => $this->profil_url ? url($this->profil_url) : null,
        ];
    }
}
