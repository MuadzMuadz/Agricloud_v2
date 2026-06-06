<?php

namespace App\Models;

use Database\Factories\StageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    /** @use HasFactory<StageFactory> */
    use HasFactory;

    protected $fillable = [
        'crop_id',
        'name',
        'order',
        'duration_days',
    ];

    public function Crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function Phases()
    {
        return $this->hasMany(Phase::class);
    }
}
