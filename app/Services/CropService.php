<?php

namespace App\Services;

use App\Models\Crop;
use Illuminate\Http\Request;
use App\Services\ImageService;

class CropService
{
    public function __construct(protected ImageService $imageService) {}

    public function getAll()
    {
        return Crop::withCount('stages')->latest()->get();
    }

    public function getById($id)
    {
        return Crop::with('stages')->find($id);
    }

    /**
     * Create crop dengan Request object untuk handle image upload
     */
    public function create(Request $request)
    {
        $data = $request->validated();
        
        // Handle image upload menggunakan ImageService
        $imageUrl = $this->imageService->upload(
            $request, // Request object
            $data['name'],
            'crop', // type - sesuaikan dengan yang ada di ImageService
            null // no old image
        );

        // Jika ada image yang diupload, gunakan URL dari ImageService
        if ($imageUrl) {
            $data['image_url'] = $imageUrl;
        }
        
        return Crop::create($data);
    }

    /**
     * Update crop dengan Request object untuk handle image upload
     */
    public function update(Request $request, $id)
    {
        $crop = Crop::find($id);
        if (!$crop) return null;

        $data = $request->validated();

        // Handle image upload menggunakan ImageService
        $imageUrl = $this->imageService->upload(
            $request, // Request object
            $data['name'] ?? $crop->name,
            'template', // type
            $crop->image_url // old image untuk delete
        );

        // Jika ada image yang diupload, update image_url
        if ($imageUrl && $imageUrl !== $crop->image_url) {
            $data['image_url'] = $imageUrl;
        }

        $crop->update($data);
        return $crop;
    }

    public function delete($id)
    {
        $crop = Crop::find($id);
        if (!$crop) return false;

        // Delete image if exists menggunakan ImageService
        if ($crop->image_url) {
            $this->imageService->delete($crop->image_url);
        }

        return $crop->delete();
    }

    /**
     * Method tambahan untuk case tanpa image upload (jika needed)
     */
    public function createWithoutImage(array $data)
    {
        return Crop::create($data);
    }

    /**
     * Method tambahan untuk update tanpa image (jika needed)
     */
    public function updateWithoutImage($id, array $data)
    {
        $crop = Crop::find($id);
        if (!$crop) return null;

        $crop->update($data);
        return $crop;
    }
}