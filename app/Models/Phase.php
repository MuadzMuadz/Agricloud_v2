<?php

namespace App\Models;

use Database\Factories\PhaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    /** @use HasFactory<PhaseFactory> */
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'stage_id',
        'status_id',
        'started_at',
        'ended_at',
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
