<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'cycle_id'    => $this->cycle_id,
            'stage_id'    => $this->stage_id,
            'status'      => $this->status?->name,
            'started_at'  => $this->started_at,
            'ended_at'    => $this->ended_at,
            'created_at'  => formatDate($this->created_at),
            'updated_at'  => formatDate($this->updated_at),
        ];
    }
}
