<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use Illuminate\Http\Request;

class CropController extends Controller
{
    public function index()
    {
        return response()->json(Crop::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $crop = Crop::create($validated);
        return response()->json(['message' => 'Tanaman berhasil ditambahkan', 'data' => $crop], 201);
    }

    public function show($id)
    {
        return response()->json(Crop::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $crop = Crop::findOrFail($id);
        $crop->update($request->all());
        return response()->json(['message' => 'Tanaman berhasil diperbarui', 'data' => $crop]);
    }

    public function destroy($id)
    {
        Crop::findOrFail($id)->delete();
        return response()->json(['message' => 'Tanaman berhasil dihapus']);
    }
}
