<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Cycle, Crop, Phase};
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
    public function index(Request $request)
    {
        $cycles = Cycle::with(['crop', 'phases', 'land'])
            ->whereHas('land', fn($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->get();

        return response()->json($cycles);
    }

    // 🔹 Detail 1 cycle (hanya milik sendiri)
    public function show(Cycle $cycle)
    {
        $this->authorize('view', $cycle);

        return response()->json(
            $cycle->load(['crop.stages', 'phases', 'land'])
        );
    }

    // 🔹 Buat cycle baru (auto-generate phases)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'land_id' => 'required|exists:lands,id',
            'crop_id' => 'required|exists:crops,id',
        ]);

        $cycle = $this->service->createCycleWithPhases($validated, $request->user());

        return response()->json([
            'message' => 'Cycle created successfully',
            'data' => $cycle->load('phases'),
        ], 201);
    }

    // 🔹 Update data cycle
    public function update(Request $request, Cycle $cycle)
    {
        $this->authorize('update', $cycle);

        $cycle->update($request->only(['status', 'start_date', 'end_date']));

        return response()->json([
            'message' => 'Cycle updated successfully',
            'data' => $cycle,
        ]);
    }

    // 🔹 Hapus cycle (soft delete)
    public function destroy(Cycle $cycle)
    {
        $this->authorize('delete', $cycle);

        $cycle->delete();

        return response()->json(['message' => 'Cycle deleted successfully']);
    }

    // 🔹 List cycle per land
    public function listByLand($landId)
    {
        $cycles = Cycle::with(['crop', 'phases'])
            ->where('land_id', $landId)
            ->whereHas('land', fn($q) => $q->where('user_id', auth()->guard()->id()))
            ->get();

        return response()->json($cycles);
    }
}
