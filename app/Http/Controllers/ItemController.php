<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Models\Categories;
use App\Models\Items;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItemController extends Controller
{
    /**
     * GET /api/warehouses/{id}/items  (Bearer)
     *
     * Daftar barang di sebuah gudang milik user. 404 (owner-scoped) bila gudang
     * bukan milik user.
     */
    public function index(Request $request, int $id): AnonymousResourceCollection
    {
        $warehouse = $request->user()
            ->Warehouses()
            ->findOrFail($id);

        $items = $warehouse->Items()
            ->with('Category')
            ->latest()
            ->get();

        return ItemResource::collection($items);
    }

    /**
     * POST /api/warehouses/{id}/items  (Bearer)
     *
     * Tambah barang ke gudang milik user. 404 (owner-scoped) bila bukan milik user.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $warehouse = $request->user()
            ->Warehouses()
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $item = $warehouse->Items()->create([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'stock' => (int) ($validated['stock'] ?? 0),
            'category_id' => $this->resolveCategoryId($validated),
        ]);

        $item->load('Category');

        return (new ItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/items/{id}  (Bearer)
     *
     * Edit barang milik user (lewat gudangnya). 404 (owner-scoped) bila gudang
     * pemilik barang bukan milik user.
     */
    public function update(Request $request, int $id): ItemResource
    {
        $item = $this->ownedItem($request, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        foreach (['name', 'unit'] as $field) {
            if (array_key_exists($field, $validated)) {
                $item->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('stock', $validated)) {
            $item->stock = (int) $validated['stock'];
        }

        if (array_key_exists('category_id', $validated) || array_key_exists('category', $validated)) {
            $item->category_id = $this->resolveCategoryId($validated);
        }

        $item->save();

        $item->load('Category');

        return new ItemResource($item);
    }

    /**
     * DELETE /api/items/{id}  (Bearer)
     *
     * Hapus barang milik user. 404 (owner-scoped) bila bukan milik user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = $this->ownedItem($request, $id);

        $item->delete();

        return response()->json(null, 204);
    }

    /**
     * Ambil barang yang gudangnya milik user, atau 404.
     */
    private function ownedItem(Request $request, int $id): Items
    {
        return Items::whereHas('Warehouse', function ($query) use ($request) {
            $query->where('farmer_id', $request->user()->id);
        })->findOrFail($id);
    }

    /**
     * Resolusi kategori dari `category_id` (eksplisit) atau nama `category`
     * (firstOrCreate). Mengembalikan null bila keduanya kosong.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveCategoryId(array $validated): ?int
    {
        if (! empty($validated['category_id'])) {
            return (int) $validated['category_id'];
        }

        if (! empty($validated['category'])) {
            return Categories::firstOrCreate(['name' => $validated['category']])->id;
        }

        return null;
    }
}
