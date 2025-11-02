<?php
namespace App\Http\Controllers;

use App\Services\WarehouseService;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WarehouseController extends Controller
{
    use ApiResponse;

    public function __construct(protected WarehouseService $service) {}

    public function index()
    {
        $data = $this->service->listForOwner();
        return $this->success($data, 'List of warehouses');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $warehouse = $this->service->create($validated);
        return $this->success($warehouse, 'Warehouse created successfully', 201);
    }

    public function show(Warehouse $warehouse)
    {
        Gate::authorize('view', $warehouse);
        $detail = $this->service->getDetail($warehouse);
        return $this->success($detail, 'Warehouse detail');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        Gate::authorize('update', $warehouse);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $updated = $this->service->update($warehouse, $validated);
        return $this->success($updated, 'Warehouse updated successfully');
    }

    public function destroy(Warehouse $warehouse)
    {
        Gate::authorize('delete', $warehouse);
        $this->service->delete($warehouse);
        return $this->success([], 'Warehouse deleted successfully');
    }
}
