<?php

namespace App\Http\Controllers;

use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class WarehouseController extends Controller
{
    /**
     * GET /api/warehouses  (Bearer)
     *
     * Daftar gudang milik user yang sedang login, beserta jumlah item.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $warehouses = $request->user()
            ->Warehouses()
            ->withCount('Items')
            ->withSum('Items', 'stock')
            ->with('Farmer')
            ->latest()
            ->get();

        return WarehouseResource::collection($warehouses);
    }

    /**
     * GET /api/warehouses/{id}  (Bearer)
     *
     * Detail satu gudang milik user. 404 (owner-scoped) bila bukan milik user.
     */
    public function show(Request $request, int $id): WarehouseResource
    {
        $warehouse = $request->user()
            ->Warehouses()
            ->withCount('Items')
            ->withSum('Items', 'stock')
            ->with(['Farmer', 'Items.Category'])
            ->findOrFail($id);

        return new WarehouseResource($warehouse);
    }

    /**
     * POST /api/warehouses  (Bearer, multipart/form-data)
     *
     * Membuat gudang baru milik user yang sedang login.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'thumbnail' => ['nullable', 'image', 'max:5120'], // maks 5 MB
        ]);

        $imageUrl = null;
        if ($request->hasFile('thumbnail')) {
            // Simpan ke disk public: storage/app/public/warehouses -> /storage/warehouses/...
            $path = $request->file('thumbnail')->store('warehouses', 'public');
            $imageUrl = '/storage/'.$path;
        }

        $warehouse = $request->user()->Warehouses()->create([
            'name' => $validated['name'],
            'location' => $validated['address'] ?? null,
            'description' => $validated['description'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'image_url' => $imageUrl,
        ]);

        $warehouse->loadCount('Items')->loadSum('Items', 'stock')->load('Farmer');

        return (new WarehouseResource($warehouse))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/warehouses/{id}  (Bearer, multipart/form-data)
     *
     * Mengubah gudang milik user. 404 (owner-scoped) bila bukan milik user.
     */
    public function update(Request $request, int $id): WarehouseResource
    {
        $warehouse = $request->user()
            ->Warehouses()
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'latitude' => ['sometimes', 'nullable', 'numeric'],
            'longitude' => ['sometimes', 'nullable', 'numeric'],
            'thumbnail' => ['sometimes', 'nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('warehouses', 'public');
            $warehouse->image_url = '/storage/'.$path;
        }

        // `address` (FE) dipetakan ke kolom `location`.
        if (array_key_exists('address', $validated)) {
            $warehouse->location = $validated['address'];
        }

        foreach (['name', 'description', 'capacity', 'latitude', 'longitude'] as $field) {
            if (array_key_exists($field, $validated)) {
                $warehouse->{$field} = $validated[$field];
            }
        }

        $warehouse->save();

        $warehouse->loadCount('Items')->loadSum('Items', 'stock')->load('Farmer');

        return new WarehouseResource($warehouse);
    }

    /**
     * DELETE /api/warehouses/{id}  (Bearer)
     *
     * Menghapus gudang. Owner-check eksplisit: 404 bila gudang tak ada, 403 bila
     * milik user lain. Item & movement ikut terhapus lewat FK cascadeOnDelete.
     * File thumbnail (bila ada) ikut dihapus dari disk public.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::find($id);

        if ($warehouse === null) {
            return response()->json(['message' => 'Gudang tidak ditemukan'], 404);
        }

        if ($warehouse->farmer_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak menghapus gudang ini'], 403);
        }

        // Hapus file thumbnail dari disk public bila ada (image_url: /storage/warehouses/...).
        if ($warehouse->image_url) {
            $path = ltrim(str_replace('/storage/', '', $warehouse->image_url), '/');
            Storage::disk('public')->delete($path);
        }

        // delete() memicu cascade FK: items & movements gudang ikut terhapus.
        $warehouse->delete();

        return response()->json(['message' => 'Gudang berhasil dihapus']);
    }
}
