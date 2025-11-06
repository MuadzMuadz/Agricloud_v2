<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Items;
use App\Models\Movements;

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

    protected static function booted()
    {
        static::deleting(function ($warehouse) {
            if ($warehouse->image_url) {
                $path = str_replace('/storage/', 'public/', $warehouse->image_url);
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
            }
        });
    }
}
