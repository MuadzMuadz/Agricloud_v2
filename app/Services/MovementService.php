<?php

namespace App\Services;

use App\Models\Movements;
use App\Models\Items;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MovementService
{
    public function listForOwner()
    {
        return Movements::whereHas('item.warehouse', fn($q) =>
            $q->where('user_id', Auth::id())
        )->with(['item', 'sourceField', 'destField'])->latest()->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $item = Items::findOrFail($data['item_id']);

            if ($item->warehouse->user_id !== Auth::id()) {
                abort(403, 'Unauthorized movement action');
            }

            // Update stock based on movement type
            match ($data['movement_type']) {
                'out' => $item->decrement('quantity', $data['quantity']),
                'in' => $item->increment('quantity', $data['quantity']),
                'transfer' => $this->handleTransfer($item, $data),
            };

            return Movements::create($data);
        });
    }

    protected function handleTransfer(Items $item, $data)
    {
        // Bisa dikembangin ke arah multi-warehouse transfer
        $item->decrement('quantity', $data['quantity']);
    }
}
