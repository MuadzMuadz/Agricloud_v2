<?php

namespace App\Http\Controllers;

use App\Models\Field;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class FieldController extends Controller
{
    use ApiResponse;

    /**
     * ✅ Ambil semua data field
     */
    public function index()
    {
        $fields = Field::with('farmer')->get();
        return $this->success($fields, 'List semua field berhasil diambil');
    }

    /**
     * ✅ Ambil satu field berdasarkan ID
     */
    public function show($id)
    {
        $field = Field::with('farmer')->find($id);
        if (!$field) {
            return $this->error('Field tidak ditemukan', 404);
        }
        return $this->success($field, 'Detail field berhasil diambil');
    }

    /**
     * ✅ Tambah field baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'area' => 'required|numeric',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->error('Validasi gagal', 422, $validator->errors());
        }

        $field = Field::create($validator->validated());
        return $this->success($field, 'Field berhasil ditambahkan', 201);
    }

    /**
     * ✅ Update field
     */
    public function update(Request $request, $id)
    {
        $field = Field::find($id);
        if (!$field) {
            return $this->error('Field tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'farmer_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'area' => 'sometimes|numeric',
            'address' => 'sometimes|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->error('Validasi gagal', 422, $validator->errors());
        }

        $field->update($validator->validated());
        return $this->success($field, 'Field berhasil diperbarui');
    }

    /**
     * ✅ Hapus field
     */
    public function destroy($id)
    {
        $field = Field::find($id);
        if (!$field) {
            return $this->error('Field tidak ditemukan', 404);
        }

        $field->delete();
        return $this->success(null, 'Field berhasil dihapus');
    }

    /**
     * ✅ Ambil field berdasarkan farmer_id
     */
    public function byFarmer($farmer_id)
    {
        $fields = Field::with('farmer')->where('farmer_id', $farmer_id)->get();
        if ($fields->isEmpty()) {
            return $this->error('Field untuk farmer ini tidak ditemukan', 404);
        }
        return $this->success($fields, 'List field berdasarkan farmer');
    }

    /**
     * ✅ Cari field berdasarkan nama, alamat, atau nama petani
     */
    public function search(Request $request)
    {
        $keyword = $request->query('q');

        if (!$keyword) {
            return $this->error('Parameter pencarian (q) diperlukan', 400);
        }

        $fields = Field::with('farmer')
            ->where('name', 'LIKE', "%{$keyword}%")
            ->orWhere('address', 'LIKE', "%{$keyword}%")
            ->orWhereHas('farmer', function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->get();

        if ($fields->isEmpty()) {
            return $this->error('Field tidak ditemukan', 404);
        }

        return $this->success($fields, 'Hasil pencarian field');
    }

    /**
     * ✅ Validasi lokasi (latitude & longitude)
     */
    public function validateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->error('Koordinat tidak valid', 422, $validator->errors());
        }

        return $this->success(null, 'Koordinat valid');
    }

    /**
     * ✅ Statistik sederhana (jumlah total field dan rata-rata area)
     */
    public function stats()
    {
        $total = Field::count();
        $averageArea = Field::avg('area');

        $stats = [
            'total_fields' => $total,
            'average_area' => round($averageArea, 2)
        ];

        return $this->success($stats, 'Statistik field');
    }

    /**
     * ✅ Update status field
     */
    public function updateStatus(Request $request, $id)
    {
        $field = Field::find($id);
        if (!$field) {
            return $this->error('Field tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validasi gagal', 422, $validator->errors());
        }

        $field->status = $request->status;
        $field->save();

        return $this->success($field, 'Status field berhasil diperbarui');
    }
}
