<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movements extends Model
{
    /** @use HasFactory<\Database\Factories\MovementsFactory> */
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'movetype_id',
        'status_id',
        'land_dest',
        'warehouse_dest',
        'quantity',
        'note',
    ];

    public function Warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function Item()
    {
        return $this->belongsTo(Items::class);
    }

    public function Movetype()
    {
        return $this->belongsTo(Movetypes::class);
    }

    public function Status()
    {
        return $this->belongsTo(Status::class);
    }

    public function LandDest()
    {
        return $this->belongsTo(Land::class, 'land_dest');
    }

    public function WarehouseDest()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_dest');
    }
}
