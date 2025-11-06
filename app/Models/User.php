<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'role_id',
        'profile_url', // ✅ diperbaiki
        'phone_number',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 🔐 Mutator untuk memastikan password selalu di-hash
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
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
