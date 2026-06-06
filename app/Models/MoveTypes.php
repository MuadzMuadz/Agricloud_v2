<?php

namespace App\Models;

use Database\Factories\MoveTypesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoveTypes extends Model
{
    /** @use HasFactory<MoveTypesFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function Movements()
    {
        return $this->hasMany(Movements::class);
    }
}
