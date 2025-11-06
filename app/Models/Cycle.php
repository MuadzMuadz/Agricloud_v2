<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'land_id',
        'crop_id',
        'status_id',
        'name',
        'description',
        'start_date',
        'end_date',
    ];

    /**
     * 🔹 Relasi ke lahan (Land)
     */
    public function Land()
    {
        return $this->belongsTo(Land::class);
    }

    /**
     * 🔹 Relasi ke tanaman (Crop)
     */
    public function Crop()
    {
        return $this->belongsTo(Crop::class);
    }

    /**
     * 🔹 Relasi ke status (Status)
     */
    public function Status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * 🔹 Relasi ke fase-fase (Phase)
     */
    public function Phases()
    {
        return $this->hasMany(Phase::class);
    }
}
