<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Land;
use App\Services\LandService;
use Illuminate\Http\Request;
use App\ApiResponse;

class AdminLandController extends Controller
{
    use ApiResponse;

    public function __construct(protected LandService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->success(($this->service->listAllForAdmin()), 'Lands retrieved successfully', 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return $this->success(
            $this->service->getDetailForAdmin($id),
            'Movement retrieved successfully',
            200
        );
    }
}
