<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Cycle, Crop, Phase, Land};
use Illuminate\Support\Facades\Auth;

class CycleController extends Controller
{
    // 🔹 GET - Semua cycle milik farmer login
    public function index()
    {
        $user = Auth::user();

        $cycles = Cycle::with(['Crop', 'Phases', 'Land'])
            ->whereHas('Land', function ($q) use ($user) {
                $q->where('farmer_id', $user->id);
            })
            ->latest()
            ->get();

        return response()->json($cycles, 200);
    }

    // 🔹 GET by ID - Detail cycle tertentu
    public function show($id)
    {
        $cycle = Cycle::with(['Crop', 'Phases', 'Land'])->find($id);

        if (!$cycle) {
            return response()->json(['message' => 'Data siklus tidak ditemukan'], 404);
        }

        return response()->json($cycle, 200);
    }

    // 🔹 POST - Tambah cycle baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'land_id' => 'required|integer|exists:lands,id',
            'crop_id' => 'required|integer|exists:crops,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status_id' => 'nullable|integer',
        ]);

        $cycle = Cycle::create($validated);

        return response()->json([
            'message' => 'Data siklus berhasil ditambahkan!',
            'data' => $cycle
        ], 201);
    }

    // 🔹 PUT - Update data cycle
    public function update(Request $request, $id)
    {
        $cycle = Cycle::find($id);

        if (!$cycle) {
            return response()->json(['message' => 'Data siklus tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date',
            'status_id' => 'nullable|integer',
        ]);

        $cycle->update($validated);

        return response()->json([
            'message' => 'Data siklus berhasil diperbarui!',
            'data' => $cycle
        ], 200);
    }

    // 🔹 DELETE - Hapus cycle
    public function destroy($id)
    {
        $cycle = Cycle::find($id);

        if (!$cycle) {
            return response()->json(['message' => 'Data siklus tidak ditemukan'], 404);
        }

        $cycle->delete();

        return response()->json(['message' => 'Data siklus berhasil dihapus!'], 200);
    }

    // 🔹 GET - Daftar cycle berdasarkan lahan
    public function listByLand($landId)
    {
        $cycles = Cycle::with(['Crop', 'Phases'])
            ->where('land_id', $landId)
            ->get();

        return response()->json($cycles, 200);
    }
}
