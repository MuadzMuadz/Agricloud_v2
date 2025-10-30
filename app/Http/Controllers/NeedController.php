<?php

namespace App\Http\Controllers;

use App\Services\NeedService;
use App\Models\Needs;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NeedController extends Controller
{
    use ApiResponse;

    public function __construct(protected NeedService $service) {}

    public function index($stageId)
    {
        $data = $this->service->listByStage($stageId);
        return $this->success($data, 'List of needs for this stage');
    }

    public function store(Request $request, $stageId)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:1',
        ]);

        $need = $this->service->requestItem($stageId, $validated);
        return $this->success($need, 'Need requested successfully', 201);
    }

    public function fulfill(Needs $need)
    {
        Gate::authorize('update', $need);
        $updated = $this->service->fulfill($need);
        return $this->success($updated, 'Need fulfilled');
    }
}
