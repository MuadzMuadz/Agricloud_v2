<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoveTypes extends Model
{
    /** @use HasFactory<\Database\Factories\MovetypesFactory> */
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
