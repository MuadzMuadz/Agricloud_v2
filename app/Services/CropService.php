<?php

namespace App\Services;

use App\Models\Crop;

class CropService
{
    public function getAll()
    {
        return Crop::withCount('stages')->latest()->get();
    }

    public function getById($id)
    {
        return Crop::with('stages')->find($id);
    }

    public function create(array $data)
    {
        return Crop::create($data);
    }

    public function update($id, array $data)
    {
        $crop = Crop::find($id);
        if (!$crop) return null;
        $crop->update($data);
        return $crop;
    }

    public function delete($id)
    {
        $crop = Crop::find($id);
        if (!$crop) return false;
        return $crop->delete();
    }
}
