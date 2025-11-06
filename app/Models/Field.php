<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'name',
        'area',
        'address',
        'latitude',
        'longitude',
        'description',
        'status',
    ];

    /**
     * ✅ Relasi ke tabel users (farmer)
     */
    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /**
     * ✅ Relasi ke tabel cycles
     */
    public function cycles()
    {
        return $this->hasMany(Cycle::class);
    }

    /**
     * ✅ Relasi ke tabel inventory_movements
     */
    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
