<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WarehouseService;
use App\Traits\ApiResponse;

class AdminWarehouseController extends Controller
{
    use ApiResponse;

    public function __construct(protected WarehouseService $service) {}

    public function index()
    {
        return $this->success($this->service->listAllForAdmin());
    }

    public function show($id)
    {
        return $this->success(
            $this->service->getDetailForAdmin($id)
        );
    }
}

