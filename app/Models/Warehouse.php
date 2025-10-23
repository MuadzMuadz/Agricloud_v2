<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFactory> */
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'name',
        'image_url',
        'description',
        'location',
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
