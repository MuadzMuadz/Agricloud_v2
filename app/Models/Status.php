<?php

namespace App\Models;

use Database\Factories\StatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    /** @use HasFactory<StatusFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function Movements()
    {
        return $this->hasMany(Movements::class);
    }

    public function Cycles()
    {
        return $this->hasMany(Cycle::class);
    }

    public function Phases()
    {
        return $this->hasMany(Phase::class);
    }
}
