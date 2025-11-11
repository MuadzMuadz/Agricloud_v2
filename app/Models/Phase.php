<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cycle;
use App\Models\Stage;
use App\Models\Status;
use App\Models\Needs;
use Carbon\Carbon;


class Phase extends Model
{
    /** @use HasFactory<\Database\Factories\PhaseFactory> */
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'stage_id',
        'status_id',
        'name',
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

    protected static function booted()
    {
        static::retrieved(function ($phase) {
            $now = Carbon::now();
            if ($phase->started_at && $phase->ended_at) {
                $statusName =
                    $now->lt($phase->started_at) ? 'Pending' :
                    ($now->gt($phase->ended_at) ? 'Completed' : 'Active');

                $statusId = Status::where('type', 'phase')
                    ->whereRaw('LOWER(name) = ?', [strtolower($statusName)])
                    ->value('id');

                if ($statusId && $statusId !== $phase->status_id) {
                    $phase->updateQuietly(['status_id' => $statusId]);
                }
            }
        });
    }

}
