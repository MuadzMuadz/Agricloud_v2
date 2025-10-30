<?php

namespace App\Services;

use App\Models\Needs;
use Illuminate\Support\Facades\Auth;

class NeedService
{
    public function listByStage($stageId)
    {
        return Needs::where('cycle_stage_id', $stageId)
            ->whereHas('item.warehouse', fn($q) => 
                $q->where('user_id', Auth::id())
            )->with('item')
            ->get();
    }

    public function requestItem($stageId, array $data)
    {
        $data['cycle_stage_id'] = $stageId;
        $data['status'] = 'requested';
        $data['requested_at'] = now();
        return Needs::create($data);
    }

    public function fulfill(Needs $need)
    {
        $need->update(['status' => 'fulfilled']);
        return $need->fresh();
    }
}
