<?php
namespace App\Http\Controllers;

use App\Services\MovementService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MovementController extends Controller
{
    use ApiResponse;

    public function __construct(protected MovementService $service) {}

    public function index()
    {
        $data = $this->service->listForOwner();
        return $this->success($data, 'List of movements');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'movement_type' => 'required|in:in,out,transfer',
            'quantity' => 'required|numeric|min:1',
            'movement_date' => 'required|date',
            'source_field_id' => 'nullable|integer',
            'dest_field_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:255',
        ]);

        $movement = $this->service->create($validated);
        return $this->success($movement, 'Movement recorded successfully', 201);
    }
}
