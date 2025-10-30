<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Phase, Cycle};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PhaseController extends Controller
{
    use AuthorizesRequests;
    // 🔹 List semua phase dalam 1 cycle
    public function index(Cycle $cycle)
    {
        $this->authorize('view', $cycle);
        return response()->json($cycle->phases);
    }

    // 🔹 Detail 1 phase
    public function show(Cycle $cycle, Phase $phase)
    {
        $this->authorize('view', $cycle);
        return response()->json($phase);
    }

    // 🔹 Update status atau tanggal phase
    public function update(Request $request, Cycle $cycle, Phase $phase)
    {
        $this->authorize('update', $cycle);

        $validated = $request->validate([
            'status' => 'in:pending,active,completed,skipped',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $phase->update($validated);

        return response()->json([
            'message' => 'Phase updated successfully',
            'data' => $phase,
        ]);
    }
}
