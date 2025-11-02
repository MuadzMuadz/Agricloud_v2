<?php
namespace App\Services;

use App\Models\Items;
use Illuminate\Support\Facades\Auth;

class ItemService
{
    public function listByWarehouse($warehouseId)
    {
        return Items::where('warehouse_id', $warehouseId)
            ->whereHas('warehouse', fn($q) => $q->where('user_id', Auth::id()))
            ->with('warehouse')
            ->get();
    }

    public function create(array $data)
    {
        $warehouseOwner = Items::where('warehouse_id', $data['warehouse_id'])
            ->with('warehouse.user')
            ->first();

        if ($warehouseOwner?->warehouse->user_id !== Auth::id()) {
            abort(403, 'You do not own this warehouse');
        }

        return Items::create($data);
    }

    public function update(Items $item, array $data)
    {
        $item->update($data);
        return $item->fresh();
    }

    public function delete(Items $item)
    {
        $item->delete();
    }
}
