<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    /** @use HasFactory<\Database\Factories\PhaseFactory> */
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'stage_id',
        'status_id',
        'name',
        'start_date',
        'end_date',
    ];

    public function Cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function Stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function Status()
    {
        return $this->belongsTo(Status::class);
    }

    public function Needs()
    {
        return $this->hasMany(Needs::class);
    }
}
