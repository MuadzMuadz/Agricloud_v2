<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\StageResource;

class CropResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description ?? null,
            'image_url'   => $this->image_url ?? null,
            'stages'      => StageResource::collection($this->whenLoaded('stages')),
            'created_at'  => formatDate($this->created_at),
            'updated_at'  => formatDate($this->updated_at),
        ];
    }
}
