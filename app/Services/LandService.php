<?php

namespace App\Services;

use App\Models\Land;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LandService
{
    public function __construct(protected ImageService $imageService) {}

    // 🔹 Ambil semua lahan milik user login
    public function listForOwner()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return Land::with('farmer')->latest()->get();
        }

        return Land::where('farmer_id', $user->id)
            ->with('farmer')
            ->latest()
            ->get();
    }

    // 🔹 Buat lahan baru
    public function create(Request $request, array $data): Land
    {
        $data['farmer_id'] = Auth::id();

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->imageService->upload(
                $request,
                $data['name'],
                'land'
            );
        }

        return Land::create($data);
    }

    // 🔹 Update lahan
    public function update(Request $request, Land $land): Land
    {
        // Ambil data dari request
        $data = $request->only(['name', 'description', 'latitude', 'longitude', 'area']);
        
        // Handle file upload
        if ($request && $request->hasFile('image')) {
            $data['image_url'] = $this->imageService->upload(
                $request,
                $data['name'] ?? $land->name,
                'land',
                $land->image_url
            );
        }


        $land->update($data);
        return $land;
    }

    // 🔹 Ambil detail
    public function find(int $id): Land
    {
        return Land::with('farmer')->findOrFail($id);
    }

    // 🔹 Hapus data (plus hapus image)
    public function delete(Land $land): bool
    {
        $this->imageService->delete($land->image_url);
        return $land->delete();
    }

    public function listAllForAdmin()
    {
        return Land::all();
    }

    public function getDetailForAdmin($id)
    {
        
        return Land::with(['farmer', 'cycles'])->findOrFail($id);
    }

}
