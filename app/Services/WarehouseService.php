<?php
namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

class WarehouseService
{
    public function listForOwner()
    {
        return Warehouse::where('user_id', Auth::id())
            ->withCount('items')
            ->latest()
            ->get();
    }

    public function create(array $data)
    {
        $data['user_id'] = Auth::id();
        return Warehouse::create($data);
    }

    public function getDetail(Warehouse $warehouse)
    {
        return $warehouse->load(['items.movements' => fn($q) => $q->latest()]);
    }

    public function update(Warehouse $warehouse, array $data)
    {
        $warehouse->update($data);
        return $warehouse->fresh();
    }

    public function delete(Warehouse $warehouse)
    {
        $warehouse->delete();
    }

    // Admin View
    public function listAllForAdmin()
    {
        return Warehouse::with(['user', 'items'])->latest()->get();
    }

    public function getDetailForAdmin($id)
    {
        return Warehouse::with(['user', 'items.movements'])->findOrFail($id);
    }
}
