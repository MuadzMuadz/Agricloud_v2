<?php
namespace App\Http\Controllers;

use App\Services\WarehouseService;
use App\Models\Warehouse;
use App\ApiResponse;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;

class WarehouseController extends Controller
{
    use ApiResponse;

    public function __construct(protected WarehouseService $service) {}

    public function index()
    {
        $data = $this->service->listForOwner();
        return $this->success(WarehouseResource::collection($data), 'List of warehouses');
    }
    public function store(WarehouseRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $imageUrl = $this->service->handleImageUpload($request, $data['name']);
            $data['image_url'] = $imageUrl;
        }

        $warehouse = $this->service->create($data);
        return $this->success(new WarehouseResource($warehouse), 'Warehouse created successfully');
    }
    

    public function show(Warehouse $warehouse)
    {
        Gate::authorize('view', $warehouse);
        $detail = $this->service->getDetail($warehouse);
        return $this->success(new WarehouseResource($detail), 'Warehouse details');
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        Gate::authorize('update', $warehouse);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->service->handleImageUpload(
                $request,
                $data['name'] ?? $warehouse->name,
                $warehouse->image_url
            );
        }

        $updated = $this->service->update($warehouse, $data);
        return $this->success($updated, 'Warehouse updated successfully');
    }

    public function destroy(Warehouse $warehouse)
    {
        Gate::authorize('delete', $warehouse);

        $this->service->delete($warehouse);
        return $this->success([], 'Warehouse deleted successfully');
    }
}
