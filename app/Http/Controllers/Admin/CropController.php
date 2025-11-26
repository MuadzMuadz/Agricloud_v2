<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Services\CropService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CropResource;
use App\Http\Resources\CropListResource;
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
            CropListResource::collection($crops),
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
            return response()->json(['message' => 'Crop not found'], 404);
        }
        return $this->success(
            new CropResource($crop),
            'Crop details'
        );
    }

    /**
     * Tambah crop baru
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'name', 'description','image_url'
        ]);

        $crop = $this->cropService->create($data);
        return $this->success(
            $crop,
            'Crop created successfully',
            201
        );
    }

    /**
     * Update crop tertentu
     */
    public function update(Request $request, $id)
    {
        $data = $request->only([
            'name', 'description', 'image_url',
        ]);

        $crop = $this->cropService->update($id, $data);
        if (!$crop) {
            return $this->error('Crop not found', 404);
        }
        return $this->success(
            $crop,
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

    public function listCrops()
    {
        $crops = $this->cropService->getAll();
        return $this->success(
            CropListResource::collection($crops),
            'List of crop templates for farmers'
        );
    }
}
