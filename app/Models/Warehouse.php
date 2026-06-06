<?php

namespace App\Models;

use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'name',
        'image_url',
        'description',
        'location',
        'capacity',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function Farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function Items()
    {
        return $this->hasMany(Items::class);
    }

    public function Movements()
    {
        return $this->hasMany(Movements::class);
    }
}
