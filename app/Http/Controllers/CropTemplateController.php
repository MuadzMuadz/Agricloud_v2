<?php

namespace App\Http\Controllers;

use App\Http\Resources\CropTemplateResource;
use App\Models\Crop;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CropTemplateController extends Controller
{
    /**
     * GET /api/crop-templates  (publik)
     *
     * Daftar template tanaman.
     */
    public function index(): AnonymousResourceCollection
    {
        return CropTemplateResource::collection(
            Crop::withSum('Stages as growth_days', 'duration_days')->get()
        );
    }
}
