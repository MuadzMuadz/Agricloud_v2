<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use App\Services\CycleService;
use App\ApiResponse;
use App\Http\Resources\CycleResource;

class AdminCycleController extends Controller
{
    use ApiResponse;

    public function __construct(protected CycleService $service){}

    public function index()
    {
        $data = $this->service->listAllforAdmin();
        return $this->success(CycleResource::collection($data), 'all cycle retrieved succesfully', 200);
    }

    public function show($id)
    {
        $detail = $this->service->getDetailforAdmin($id);
        return $this->success(CycleResource::collection($detail), 'cycle detail retrieved successfully', 200);
    }
}
