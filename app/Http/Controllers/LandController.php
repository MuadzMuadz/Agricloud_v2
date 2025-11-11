<?php

namespace App\Http\Controllers;

use App\Http\Requests\LandRequest;
use App\Http\Resources\LandResource;
use App\Services\LandService;
use App\ApiResponse;

class LandController extends Controller
{
    use ApiResponse;
    public function __construct(protected LandService $service) {}

    public function index()
    {
        $lands = $this->service->listForOwner();
        return $this->success(LandResource::collection($lands), 'Data lahan berhasil diambil');
    }

    public function store(LandRequest $request)
    {
        $land = $this->service->create($request, $request->validated());
        return $this->success(new LandResource($land), 'Data lahan berhasil ditambahkan', 201);
    }

    public function show($id)
    {
        $land = $this->service->find($id);
        if (!$land) {
            return $this->error('Data lahan tidak ditemukan', 404);
        }
        return $this->success(new LandResource($land), 'Detail lahan berhasil diambil');
    }

    public function update(LandRequest $request, $id)
    {
        $land = $this->service->find($id);
        if (!$land) {
            return $this->error('Data lahan tidak ditemukan', 404);
        }

        $updated = $this->service->update($request, $land, $request->validated());
        return $this->success(new LandResource($updated), 'Data lahan berhasil diperbarui');
    }

    public function destroy($id)
    {
        $land = $this->service->find($id);
        if (!$land) {
            return $this->error('Data lahan tidak ditemukan', 404);
        }

        $this->service->delete($land);
        return $this->success(null, 'Data lahan berhasil dihapus');
    }
}
