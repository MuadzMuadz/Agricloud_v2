<?php
namespace App\Http\Controllers;

use App\Services\MovementService;
use App\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Requests\MovementRequest;

class MovementController extends Controller
{
    use ApiResponse;

    public function __construct(protected MovementService $service) {}

    public function index()
    {
        $data = $this->service->listForOwner();
        return $this->success($data, 'Movements retrieved successfully');
    }

    public function store(MovementRequest $request)
    {
        $validated = $request->validated();

        $movement = $this->service->create($validated);
        return $this->success($movement, 'Movement recorded successfully', 201);
    }
}
