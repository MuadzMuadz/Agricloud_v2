<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Crop;
use App\Services\CropService;

class CropController extends Controller
{
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
        return response()->json($crops);
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
        return response()->json($crop);
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
        return response()->json(['message' => 'Crop created successfully', 'data' => $crop], 201);
    }

    /**
     * Update crop tertentu
     */
    public function update(Request $request, $id)
    {
        $data = $request->only([
            'name', 'latin_name', 'description', 'growth_duration',
            'optimal_temp_min', 'optimal_temp_max', 'optimal_ph_min',
            'optimal_ph_max', 'image_url'
        ]);

        $crop = $this->cropService->update($id, $data);
        if (!$crop) {
            return response()->json(['message' => 'Crop not found'], 404);
        }
        return response()->json(['message' => 'Crop updated successfully', 'data' => $crop]);
    }

    /**
     * Hapus crop
     */
    public function destroy($id)
    {
        $deleted = $this->cropService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Crop not found'], 404);
        }
        return response()->json(['message' => 'Crop deleted successfully']);
    }
}
