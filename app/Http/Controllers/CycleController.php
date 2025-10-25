<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use Illuminate\Http\Request;

class CycleController extends Controller
{
    public function index()
    {
        return response()->json(Cycle::with('Land', 'Crop')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'land_id' => 'required|exists:lands,id',
            'crop_id' => 'required|exists:crops,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|string',
        ]);

        $cycle = Cycle::create($validated);
        return response()->json(['message' => 'Siklus berhasil ditambahkan', 'data' => $cycle], 201);
    }

    public function show($id)
    {
        return response()->json(Cycle::with('Land', 'Crop')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $cycle = Cycle::findOrFail($id);
        $cycle->update($request->all());
        return response()->json(['message' => 'Siklus berhasil diperbarui', 'data' => $cycle]);
    }

    public function destroy($id)
    {
        Cycle::findOrFail($id)->delete();
        return response()->json(['message' => 'Siklus berhasil dihapus']);
    }
}
