<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return response()->json(Warehouse::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string',
            'capacity' => 'nullable|numeric',
        ]);

        $warehouse = Warehouse::create($validated);
        return response()->json(['message' => 'Gudang berhasil ditambahkan', 'data' => $warehouse], 201);
    }

    public function show($id)
    {
        return response()->json(Warehouse::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->all());
        return response()->json(['message' => 'Gudang berhasil diperbarui', 'data' => $warehouse]);
    }

    public function destroy($id)
    {
        Warehouse::findOrFail($id)->delete();
        return response()->json(['message' => 'Gudang berhasil dihapus']);
    }
}
