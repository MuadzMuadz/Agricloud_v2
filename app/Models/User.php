<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'role_id',
        'profil_url',
        'phone_number',
        'email',
        'password',
        'google_id',
        'provider',
        'settings',
        'weather_mode',
        'weather_district',
        'weather_lat',
        'weather_lon',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
            'weather_lat' => 'float',
            'weather_lon' => 'float',
        ];
    }

    public function Role()
    {
        return $this->belongsTo(Role::class);
    }

    public function Lands()
    {
        return $this->hasMany(Land::class, 'farmer_id');
    }

    public function Warehouses()
    {
        return $this->hasMany(Warehouse::class, 'farmer_id');
    }
}
