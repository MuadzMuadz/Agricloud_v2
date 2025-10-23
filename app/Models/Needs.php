<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Needs extends Model
{
    /** @use HasFactory<\Database\Factories\NeedsFactory> */
    use HasFactory;

    protected $fillable = [
        'phase_id',
        'item_id',
        'quantity_needed',
    ];

    public function Phase()
    {
        return $this->belongsTo(Phase::class);
    }

    public function Item()
    {
        return $this->belongsTo(Items::class);
    }
}
