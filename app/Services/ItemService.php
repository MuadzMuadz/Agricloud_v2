<?php

namespace App\Services;

use App\Models\Items;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

class ItemService
{
    public function listByWarehouse($warehouseId)
    {
        return Items::where('warehouse_id', $warehouseId)
            ->with('category')
            ->latest()
            ->get();
    }

    public function create(array $data)
    {
        $warehouse = Warehouse::findOrFail($data['warehouse_id']);

        if ($warehouse->farmer_id !== Auth::id()) {
            abort(403, 'Tidak diizinkan menambahkan item ke gudang ini.');
        }

        return Items::create($data);
    }

    public function update(Items $item, array $data)
    {
        $item->load('warehouse'); // pastiin relasinya udah ada

        if (!$item->warehouse) {
            abort(404, 'Gudang tidak ditemukan untuk item ini.');
        }

        if ($item->warehouse->farmer_id !== Auth::id()) {
            abort(403, 'Tidak diizinkan mengubah item ini.');
        }

        $item->update($data);
        return $item->fresh(['category', 'warehouse']);
    }

    public function delete(Items $item)
    {
        $item->load('warehouse');

        if (!$item->warehouse) {
            abort(404, 'Gudang tidak ditemukan untuk item ini.');
        }

        if ($item->warehouse->farmer_id !== Auth::id()) {
            abort(403, 'Tidak diizinkan menghapus item ini.');
        }

        $item->delete();
    }

    // Admin use
    public function listAllForAdmin()
    {
        return Items::with(['warehouse.farmer', 'category'])->latest()->get();
    }

    public function getDetailForAdmin($id)
    {
        return Items::with(['warehouse.farmer', 'category'])->findOrFail($id);
    }
}
