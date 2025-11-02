<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\StageService;

class StageController extends Controller
{
    protected $stageService;

    public function __construct(StageService $stageService)
    {
        $this->stageService = $stageService;
    }

    /**
     * List semua stages (bisa pakai filter crop_id)
     */
    public function index(Request $request)
    {
        $cropId = $request->query('crop_id');
        $stages = $this->stageService->getAll($cropId);
        return response()->json($stages);
    }

    /**
     * List stages by crop (nested route)
     */
    public function indexByCrop($cropId)
    {
        $stages = $this->stageService->getByCropId($cropId);
        return response()->json($stages);
    }

    /**
     * Tambah stage ke crop tertentu
     */
    public function storeByCrop(Request $request, $cropId)
    {
        $data = $request->only(['name', 'day_start', 'day_end', 'description']);
        $stage = $this->stageService->createByCrop($cropId, $data);

        if (!$stage) {
            return response()->json(['message' => 'Crop not found'], 404);
        }

        return response()->json(['message' => 'Stage created successfully', 'data' => $stage], 201);
    }

    /**
     * Detail satu stage
     */
    public function show($id)
    {
        $stage = $this->stageService->getById($id);
        if (!$stage) {
            return response()->json(['message' => 'Stage not found'], 404);
        }
        return response()->json($stage);
    }

    /**
     * Update stage tertentu
     */
    public function update(Request $request, $id)
    {
        $data = $request->only(['name', 'day_start', 'day_end', 'description']);
        $stage = $this->stageService->update($id, $data);

        if (!$stage) {
            return response()->json(['message' => 'Stage not found'], 404);
        }

        return response()->json(['message' => 'Stage updated successfully', 'data' => $stage]);
    }

    /**
     * Hapus stage tertentu
     */
    public function destroy($id)
    {
        $deleted = $this->stageService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Stage not found'], 404);
        }
        return response()->json(['message' => 'Stage deleted successfully']);
    }
}
