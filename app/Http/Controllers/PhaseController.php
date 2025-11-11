<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Phase, Cycle};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Resources\PhaseResource;
use App\ApiResponse;
use GrahamCampbell\ResultType\Success;

class PhaseController extends Controller
{
    use AuthorizesRequests , ApiResponse;
    // 🔹 List semua phase dalam 1 cycle
    public function index(Cycle $cycle)
    {
        $this->authorize('view', $cycle);
        return $this->success(
            PhaseResource::collection($cycle->phases),
            'List of phases in the cycle'
        );
    }

    // 🔹 Detail 1 phase
    public function show(Cycle $cycle, Phase $phase)
    {
        $this->authorize('view', $cycle);
        return $this->success(
            new PhaseResource($phase),
            'Phase details'
        );
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

        return $this->success(
            new PhaseResource($phase),
            'Phase updated successfully'
        );
    }
}
