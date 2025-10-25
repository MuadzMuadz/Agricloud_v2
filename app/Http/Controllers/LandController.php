<?php

namespace App\Http\Controllers;

use App\Models\Land;
use Illuminate\Http\Request;

class LandController extends Controller
{
    // 🔹 1. GET - Ambil semua data
    public function index()
    {
        return response()->json(Land::all(), 200);
    }

    // 🔹 2. POST - Tambah data baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'farmer_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'area' => 'nullable|numeric',
        ]);

        $land = Land::create($validated);

        return response()->json([
            'message' => 'Data lahan berhasil ditambahkan!',
            'data' => $land
        ], 201);
    }

    // 🔹 3. GET by ID - Ambil satu data
    public function show($id)
    {
        $land = Land::find($id);

        if (!$land) {
            return response()->json(['message' => 'Data lahan tidak ditemukan'], 404);
        }

        return response()->json($land, 200);
    }

    // 🔹 4. PUT - Update data
    public function update(Request $request, $id)
    {
        $land = Land::find($id);

        if (!$land) {
            return response()->json(['message' => 'Data lahan tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'farmer_id' => 'sometimes|integer|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'area' => 'nullable|numeric',
        ]);

        $land->update($validated);

        return response()->json([
            'message' => 'Data lahan berhasil diperbarui!',
            'data' => $land
        ], 200);
    }

    // 🔹 5. DELETE - Hapus data
    public function destroy($id)
    {
        $land = Land::find($id);

        if (!$land) {
            return response()->json(['message' => 'Data lahan tidak ditemukan'], 404);
        }

        $land->delete();

        return response()->json(['message' => 'Data lahan berhasil dihapus!'], 200);
    }
}
