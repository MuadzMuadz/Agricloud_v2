<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Services\ItemService;
use App\ApiResponse;

class AdminItemController extends Controller
{
    use ApiResponse;

    public function __construct(protected ItemService $service) {}

    public function index()
    {
        $data = $this->service->listAllForAdmin();
        return $this->success(ItemResource::collection($data), 'List semua item (admin)');
    }

    public function show($id)
    {
        $detail = $this->service->getDetailForAdmin($id);
        return $this->success(new ItemResource($detail), 'Detail item (admin)');
    }
}
