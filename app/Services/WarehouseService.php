<?php

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

class WarehouseService
{
    public function __construct(protected ImageService $imageService) {}

    /**
     * Ambil daftar warehouse milik user (farmer)
     */
    public function listForOwner()
    {
        return Warehouse::where('farmer_id', Auth::id())
            ->withCount('items')
            ->latest()
            ->get();
    }

    /**
     * Tambah warehouse baru
     */
    public function create(array $data, $request = null)
    {
        $data['farmer_id'] = Auth::id();

        // Upload gambar kalau ada
        if ($request && $request->hasFile('image')) {
            $data['image_url'] = $this->imageService->upload(
                $request,
                $data['name'],
                'warehouse'
            );
        }

        return Warehouse::create($data);
    }

    /**
     * Detail warehouse (dengan items & movements)
     */
    public function getDetail(Warehouse $warehouse)
    {
        return $warehouse->load(['items.movements' => fn($q) => $q->latest()]);
    }

    /**
     * Update warehouse (termasuk gambar)
     */
    public function update(Warehouse $warehouse, array $data, $imageFile = null)
    {
        if ($imageFile) {
            $data['image_url'] = $this->imageService->upload(
                $imageFile,
                $data['name'] ?? $warehouse->name,
                'warehouse',
                $warehouse->image_url
            );
        }

        $warehouse->update($data);
        return $warehouse->fresh();
    }

    /**
     * Hapus warehouse + gambar
     */
    public function delete(Warehouse $warehouse)
    {
        if ($warehouse->image_url) {
            $this->imageService->delete($warehouse->image_url);
        }

        $warehouse->delete();
    }

    // ========================= ADMIN ZONE ========================= //

    /**
     * Daftar semua warehouse untuk admin
     */
    public function listAllForAdmin()
    {
        return Warehouse::with(['farmer', 'items'])->latest()->get();
    }

    /**
     * Detail warehouse versi admin
     */
    public function getDetailForAdmin($id)
    {
        return Warehouse::with(['farmer', 'items'])->findOrFail($id);
    }
}
