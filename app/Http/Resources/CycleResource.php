<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status?->name ?? 'N/A',
            'start_date'  => formatDate($this->start_date),
            'end_date'    => formatDate($this->end_date),
            'land'        => [
                'id'   => $this->land->id,
                'name' => $this->land->name,
            ],
            'crop' => [
                'id'   => $this->crop->id,
                'name' => $this->crop->name,
                'image'=> $this->crop->image_url,
            ],
            'phases' => PhaseResource::collection($this->whenLoaded('phases')),
            'created_at' => formatDate($this->created_at),
            'updated_at' => formatDate($this->updated_at),
        ];
    }
}
