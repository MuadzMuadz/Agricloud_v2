<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Services\CropService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CropResource;
use App\Http\Requests\CropRequest;
use App\ApiResponse;

class CropController extends Controller
{
    use ApiResponse;
    protected $cropService;

    public function __construct(CropService $cropService)
    {
        $this->cropService = $cropService;
    }

    /**
     * List semua crops
     */
    public function index()
    {
        $crops = $this->cropService->getAll();
        return $this->success(
            CropResource::collection($crops),
            'List of all crops'
        );
    }

    /**
     * Detail crop (include stages)
     */
    public function show($id)
    {
        $crop = $this->cropService->getById($id);
        if (!$crop) {
            return $this->error('Crop not found', 404);
        }
        return $this->success(
            new CropResource($crop),
            'Crop details'
        );
    }

    /**
     * Tambah crop baru
     */
    public function store(CropRequest $request)
    {
        $crop = $this->cropService->create($request);
        return $this->success(
            new CropResource($crop),
            'Crop created successfully',
            201
        );
    }

    /**
     * Update crop tertentu
     */
    public function update(CropRequest $request, $id)
    {
        $crop = $this->cropService->update($request, $id);
        if (!$crop) {
            return $this->error('Crop not found', 404);
        }
        return $this->success(
            new CropResource($crop),
            'Crop updated successfully',
            200
        );
    }

    /**
     * Hapus crop
     */
    public function destroy($id)
    {
        $deleted = $this->cropService->delete($id);
        if (!$deleted) {
            return $this->error('Crop not found', 404);
        }
        return $this->success(
            [],
            'Crop deleted successfully',
            200
        );
    }

    /**
     * List crops untuk farmer
     */
    public function listCrops()
    {
        $crops = $this->cropService->getAll();
        return $this->success(
            CropResource::collection($crops),
            'List of crop templates for farmers'
        );
    }
}