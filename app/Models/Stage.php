<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    /** @use HasFactory<\Database\Factories\StageFactory> */
    use HasFactory;

    protected $fillable = [
        'crop_id',
        'name',
        'description',
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
