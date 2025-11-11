<?php
namespace App\Http\Controllers;

use App\Services\ItemService;
use App\Models\Items;
use App\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ItemController extends Controller
{
    use ApiResponse;

    public function __construct(protected ItemService $service) {}

    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $items = $this->service->listByWarehouse($warehouseId);
        return $this->success($items, 'List of items');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:150',
            'sku' => 'nullable|string|max:100',
            'batch' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'min_threshold' => 'nullable|numeric|min:0',
        ]);

        $item = $this->service->create($validated);
        return $this->success($item, 'Item created successfully', 201);
    }

    public function show(Items $item)
    {
        Gate::authorize('view', $item);
        return $this->success($item->load('warehouse'), 'Item detail');
    }

    public function update(Request $request, Items $item)
    {
        Gate::authorize('update', $item);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'quantity' => 'sometimes|numeric|min:0',
            'category' => 'nullable|string|max:100',
        ]);

        $updated = $this->service->update($item, $validated);
        return $this->success($updated, 'Item updated successfully');
    }

    public function destroy(Items $item)
    {
        Gate::authorize('delete', $item);
        $this->service->delete($item);
        return $this->success([], 'Item deleted successfully');
    }
}
