<?php
namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function handleImageUpload($request, $warehouseName, $oldImageUrl = null): ?string
    {
        $user = Auth::user();
        $userDir = "user/{$user->username}_{$user->id}/warehouses";
        $filename = Str::slug($warehouseName) . '.' . $request->file('image')->getClientOriginalExtension();

        // Hapus file lama kalau ada
        if ($oldImageUrl) {
            $oldPath = str_replace('/storage/', 'public/', $oldImageUrl);
            if (Storage::exists($oldPath)) {
                Storage::delete($oldPath);
            }
        }

        // Simpan file baru
        $path = $request->file('image')->storeAs($userDir, $filename, 'public');
        return Storage::url($path);
    }
}
