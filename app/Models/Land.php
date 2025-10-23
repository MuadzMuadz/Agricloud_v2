<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Land extends Model
{
    /** @use HasFactory<\Database\Factories\LandFactory> */
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'name',
        'image_url',
        'description',
        'latitude',
        'longitude',
        'area',
    ];

    public function Farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function Cycles()
    {
        return $this->hasMany(Cycle::class);
    }

    public function Movements()
    {
        return $this->hasMany(Movements::class, 'land_dest');
    }

}
