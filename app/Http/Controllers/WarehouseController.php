<?php

namespace App\Http\Controllers;

use App\Services\{WarehouseService, ImageService};
use App\Models\Warehouse;
use App\ApiResponse;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;

class WarehouseController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected WarehouseService $warehouseService,
        protected ImageService $imageService
    ) {}

    public function index()
    {
        $data = $this->warehouseService->listForOwner();
        return $this->success(WarehouseResource::collection($data), 'List of warehouses');
    }

    public function store(WarehouseRequest $request)
    {
        $data = $request->validated();
        $warehouse = $this->warehouseService->create($data, $request);

        return $this->success(new WarehouseResource($warehouse), 'Warehouse created successfully', 201);
    }

    public function show(Warehouse $warehouse)
    {
        Gate::authorize('view', $warehouse);
        $detail = $this->warehouseService->getDetail($warehouse);

        return $this->success(new WarehouseResource($detail), 'Warehouse details');
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        Gate::authorize('update', $warehouse);

        $data = $request->validated();
        $updated = $this->warehouseService->update($warehouse, $data, $request);

        return $this->success(new WarehouseResource($updated), 'Warehouse updated successfully');
    }

    public function destroy(Warehouse $warehouse)
    {
        Gate::authorize('delete', $warehouse);
        $this->warehouseService->delete($warehouse);

        return $this->success([], 'Warehouse deleted successfully');
    }
}
