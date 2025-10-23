<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    /** @use HasFactory<\Database\Factories\ItemsFactory> */
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'category_id',
        'name',
        'unit',
        'stock',
    ];

    public function Warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function Category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function Movements()
    {
        return $this->hasMany(Movements::class);
    }

    public function Needs()
    {
        return $this->hasMany(Needs::class);
    }
}
