<?php

namespace App\Models;

use Database\Factories\CropFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    /** @use HasFactory<CropFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
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
