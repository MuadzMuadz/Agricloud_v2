<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'warehouse_id'    => $this->warehouse_id,
            'warehouse_name'  => $this->warehouse?->name,
            'item_id'         => $this->item_id,
            'item_name'       => $this->item?->name,
            'movetype'        => $this->movetype?->name,
            'status'          => $this->status?->name,
            'land_dest'       => $this->landDest?->name,
            'warehouse_dest'  => $this->warehouseDest?->name,
            'quantity'        => $this->quantity,
            'note'            => $this->note,
            'created_at'      => formatDate($this->created_at),
        ];
    }
}
