<?php

namespace App\Http\Controllers;

use App\Models\Land;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class LandController extends Controller
{
    use ApiResponse;

    // ✅ Ambil semua data lahan (termasuk relasi farmer)
    public function index()
    {
        $lands = Land::with('farmer')->get();
        return $this->success($lands, 'List semua lahan');
    }

    // ✅ Ambil detail 1 lahan
    public function show($id)
    {
        $land = Land::with(['farmer', 'cycles'])->find($id);
        if (!$land) {
            return $this->error('Data lahan tidak ditemukan', 404);
        }

        return $this->success($land, 'Detail lahan ditemukan');
    }

    // ✅ Tambah lahan baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'area' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->error('Validasi gagal', 422, $validator->errors());
        }

        $land = Land::create($validator->validated());
        return $this->success($land, 'Data lahan berhasil ditambahkan!', 201);
    }

    // ✅ Update lahan
    public function update(Request $request, $id)
    {
        $land = Land::find($id);
        if (!$land) {
            return $this->error('Data lahan tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'farmer_id' => 'sometimes|integer|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'area' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->error('Validasi gagal', 422, $validator->errors());
        }

        $land->update($validator->validated());
        return $this->success($land, 'Data lahan berhasil diperbarui!');
    }

    // ✅ Hapus lahan
    public function destroy($id)
    {
        $land = Land::find($id);
        if (!$land) {
            return $this->error('Data lahan tidak ditemukan', 404);
        }

        $land->delete();
        return $this->success(null, 'Data lahan berhasil dihapus!');
    }

    // ✅ Pencarian lahan berdasarkan nama, deskripsi, atau nama petani
    public function search(Request $request)
    {
        $keyword = $request->query('q');
        if (!$keyword) {
            return $this->error('Parameter pencarian (q) diperlukan', 400);
        }

        $lands = Land::with('farmer')
            ->where('name', 'LIKE', "%{$keyword}%")
            ->orWhere('description', 'LIKE', "%{$keyword}%")
            ->orWhereHas('farmer', function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->get();

        if ($lands->isEmpty()) {
            return $this->error('Tidak ada hasil untuk pencarian ini', 404);
        }

        return $this->success($lands, 'Hasil pencarian lahan');
    }

    // ✅ Statistik sederhana (total lahan dan rata-rata luas)
    public function stats()
    {
        $total = Land::count();
        $averageArea = Land::avg('area');

        $stats = [
            'total_lands' => $total,
            'average_area' => round($averageArea, 2)
        ];

        return $this->success($stats, 'Statistik lahan');
    }
}
