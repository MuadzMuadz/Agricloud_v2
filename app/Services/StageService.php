<?php

namespace App\Services;

use App\Models\Stage;
use App\Models\Crop;

class StageService
{
    public function getAll($cropId = null)
    {
        $query = Stage::query();
        if ($cropId) {
            $query->where('crop_id', $cropId);
        }
        return $query->with('crop:id,name')->get();
    }

    public function getByCropId($cropId)
    {
        return Stage::where('crop_id', $cropId)->get();
    }

    public function getById($id)
    {
        return Stage::with('crop:id,name')->find($id);
    }

    public function createByCrop($cropId, array $data)
    {
        $crop = Crop::find($cropId);
        if (!$crop) return null;

        $data['crop_id'] = $cropId;
        return Stage::create($data);
    }

    public function update($id, array $data)
    {
        $stage = Stage::find($id);
        if (!$stage) return null;

        $stage->update($data);
        return $stage;
    }

    public function delete($id)
    {
        $stage = Stage::find($id);
        if (!$stage) return false;

        return $stage->delete();
    }
}
