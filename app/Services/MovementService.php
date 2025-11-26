<?php

namespace App\Services;

use App\Models\Movements;
use App\Models\Items;
use App\Models\MoveTypes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MovementService
{
    public function listForOwner()
    {
        return Movements::whereHas('item.warehouse', fn($q) =>
            $q->where('farmer_id', Auth::id())
        )->with(['item', 'warehouse', 'movetype', 'status', 'landDest', 'warehouseDest'])->latest()->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $item = Items::findOrFail($data['item_id']);

            if ($item->warehouse->farmer_id !== Auth::id()) {
                abort(403, 'Unauthorized movement action');
            }

            // Dapatkan movetype berdasarkan ID dari data
            $movetype = MoveTypes::findOrFail($data['movetype_id']);
            
            // Update stock berdasarkan nama movement type
            match ($movetype->name) {
                'out' => $item->decrement('quantity', $data['quantity']),
                'in' => $item->increment('quantity', $data['quantity']),
                'transfer' => $this->handleTransfer($item, $data),
                default => null // Tambahkan default case untuk handle unknown types
            };

            return Movements::create($data);
        });
    }

    protected function handleTransfer(Items $item, $data)
    {
        // Bisa dikembangin ke arah multi-warehouse transfer
        $item->decrement('quantity', $data['quantity']);
    }

    public function listAllForAdmin()
    {
        return Movements::with(['item.warehouse.farmer', 'movetype', 'status', 'landDest', 'warehouseDest'])->latest()->get();
    }

    public function getDetailForAdmin($id)
    {
        return Movements::with(['item.warehouse.farmer', 'movetype', 'status', 'landDest', 'warehouseDest'])->findOrFail($id);
    }
}
