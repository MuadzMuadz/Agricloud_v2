<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    /** @use HasFactory<\Database\Factories\CropFactory> */
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'image_url',
    ];

    public function Cycles()
    {
        return $this->hasMany(Cycle::class);
    }

    public function Stages()
    {
        return $this->hasMany(Stage::class);
    }
}
