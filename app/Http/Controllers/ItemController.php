<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Categories;
use App\Models\Items;
use App\Services\ItemService;
use App\ApiResponse;
use Illuminate\Support\Facades\Gate;

class ItemController extends Controller
{
    use ApiResponse;

    public function __construct(protected ItemService $service) {}

    public function indexByWarehouse($warehouse_id)
    {
        $data = $this->service->listByWarehouse($warehouse_id);
        return $this->success(ItemResource::collection($data), 'Daftar item dalam gudang');
    }

    public function store(ItemRequest $request)
    {
        $item = $this->service->create($request->validated());
        return $this->success(new ItemResource($item), 'Item berhasil ditambahkan');
    }

    public function show(Items $item)
    {
        Gate::authorize('view', $item);
        return $this->success(new ItemResource($item), 'Detail item');
    }

    public function update(ItemRequest $request, Items $item)
    {
        Gate::authorize('update', $item);
        $updated = $this->service->update($item, $request->validated());
        return $this->success(new ItemResource($updated), 'Item berhasil diperbarui');
    }

    public function destroy(Items $item)
    {
        Gate::authorize('delete', $item);
        $this->service->delete($item);
        return $this->success([], 'Item berhasil dihapus');
    }
    public function getcategories()
    {
        $categories = Categories::get();
        return $this->success($categories, 'Daftar kategori item');
    }
}
