<?php

namespace App\Http\Controllers;

use App\Http\Requests\CycleRequest;
use App\Http\Resources\CycleResource;
use App\Models\Cycle;
use App\Services\CycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CycleController extends Controller
{
    use AuthorizesRequests;

    protected $service;

    public function __construct(CycleService $service)
    {
        $this->service = $service;
    }

    // 🔹 List semua cycle milik farmer
    public function index()
    {
        $cycles = Cycle::with(['crop', 'phases', 'land'])
            ->whereHas('land', fn($q) => $q->where('farmer_id', auth()->guard()->id()))
            ->latest()
            ->get();

        return CycleResource::collection($cycles);
    }

    // 🔹 Detail 1 cycle (hanya milik sendiri)
    public function show(Cycle $cycle)
    {
        $this->authorize('view', $cycle);

        return new CycleResource($cycle->load(['crop', 'phases.status', 'land', 'status']));
    }

    // 🔹 Buat cycle baru (auto-generate phases)
    public function store(CycleRequest $request)
    {
        $cycle = $this->service->createCycleWithPhases(
            $request->validated(),
            auth()->guard()->user()
        );

        return (new CycleResource($cycle->load('phases')))
            ->additional(['message' => 'Cycle created successfully']);
    }

    // 🔹 Update data cycle
    public function update(CycleRequest $request, Cycle $cycle)
    {
        $cycle->update($request->validated());

        return (new CycleResource($cycle))
            ->additional(['message' => 'Cycle updated successfully']);
    }

    // 🔹 Hapus cycle (soft delete)
    public function destroy(    Cycle $cycle)
    {
        $cycle->delete();

        return response()->json(['message' => 'Cycle deleted successfully']);
    }

    // 🔹 List cycle per land
    public function listByLand($landId)
    {
        $cycles = Cycle::with(['crop', 'phases'])
            ->where('land_id', $landId)
            ->whereHas('land', fn($q) => $q->where('farmer_id', auth()->guard()->id()))
            ->get();

        return CycleResource::collection($cycles);
    }
}
