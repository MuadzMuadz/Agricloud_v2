<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Phase, Cycle};
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PhaseController extends Controller
{
    use AuthorizesRequests;

    // 🔹 GET - Semua phase dalam 1 cycle
    public function index(Cycle $cycle)
    {
        $this->authorize('view', $cycle);
        $phases = $cycle->phases()->orderBy('start_date', 'asc')->get();

        return response()->json($phases, 200);
    }

    // 🔹 GET - Detail 1 phase
    public function show(Cycle $cycle, Phase $phase)
    {
        $this->authorize('view', $cycle);

        if ($phase->cycle_id !== $cycle->id) {
            return response()->json(['message' => 'Phase tidak sesuai dengan cycle ini'], 400);
        }

        return response()->json($phase, 200);
    }

    // 🔹 POST - Tambah phase baru ke dalam 1 cycle
    public function store(Request $request, Cycle $cycle)
    {
        $this->authorize('update', $cycle);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,active,completed,skipped',
        ]);

        $validated['cycle_id'] = $cycle->id;

        $phase = Phase::create($validated);

        return response()->json([
            'message' => 'Phase berhasil ditambahkan!',
            'data' => $phase,
        ], 201);
    }

    // 🔹 PUT - Update phase (status atau tanggal)
    public function update(Request $request, Cycle $cycle, Phase $phase)
    {
        $this->authorize('update', $cycle);

        if ($phase->cycle_id !== $cycle->id) {
            return response()->json(['message' => 'Phase tidak sesuai dengan cycle ini'], 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,active,completed,skipped',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $phase->update($validated);

        return response()->json([
            'message' => 'Phase berhasil diperbarui!',
            'data' => $phase,
        ], 200);
    }

    // 🔹 DELETE - Hapus phase
    public function destroy(Cycle $cycle, Phase $phase)
    {
        $this->authorize('update', $cycle);

        if ($phase->cycle_id !== $cycle->id) {
            return response()->json(['message' => 'Phase tidak sesuai dengan cycle ini'], 400);
        }

        $phase->delete();

        return response()->json(['message' => 'Phase berhasil dihapus!'], 200);
    }
}
