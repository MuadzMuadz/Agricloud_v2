<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    /** @use HasFactory<\Database\Factories\CycleFactory> */
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

    public function Land()
    {
        return $this->belongsTo(Land::class);
    }

    public function Crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function Status()
    {
        return $this->belongsTo(Status::class);
    }
    
    public function Phases()
    {
        return $this->hasMany(Phase::class);
    }
}
