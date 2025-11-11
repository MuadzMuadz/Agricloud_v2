<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Land;
use App\Models\Crop;
use App\Models\Status;
use App\Models\Phase;
use Carbon\Carbon;

class Cycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'land_id',
        'crop_id',
        'status_id',
        'name',
        'description',
        'start_date',
        'end_date',
    ];

    public function land()
    {
        return $this->belongsTo(Land::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function phases()
    {
        return $this->hasMany(Phase::class);
    }

    protected static function booted()
    {
        static::retrieved(function ($cycle) {
            $now = Carbon::now();

            if ($cycle->start_date && $cycle->end_date) {
                $statusName =
                    $now->lt($cycle->start_date) ? 'Pending' :
                    ($now->gt($cycle->end_date) ? 'Completed' : 'Active');

                $statusId = Status::where('type', 'cycle')
                    ->whereRaw('LOWER(name) = ?', [strtolower($statusName)])
                    ->value('id');

                if ($statusId && $statusId !== $cycle->status_id) {
                    $cycle->updateQuietly(['status_id' => $statusId]);
                }
            }

            // auto-refresh semua phase di dalamnya
            $cycle->phases->each->refresh();
        });
    }

}
