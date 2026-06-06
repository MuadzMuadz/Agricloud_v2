<?php

namespace App\Http\Controllers;

use App\Http\Resources\LandResource;
use App\Models\Land;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class MyFieldController extends Controller
{
    /**
     * GET /api/myfields  (Bearer)
     *
     * Daftar lahan milik user yang sedang login.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $lands = $request->user()
            ->Lands()
            ->with($this->landEagerLoad())
            ->latest()
            ->get();

        return LandResource::collection($lands);
    }

    /**
     * GET /api/myfields/{id}  (Bearer)
     *
     * Detail satu lahan milik user. 404 (owner-scoped) bila bukan milik user.
     */
    public function show(Request $request, int $id): LandResource
    {
        $land = $request->user()
            ->Lands()
            ->with($this->landEagerLoad())
            ->findOrFail($id);

        return new LandResource($land);
    }

    /**
     * Eager-load lahan yang konsisten untuk index, show, & store:
     * pemilik + siklus aktif (tumpang sari) beserta tanaman & fase aktifnya,
     * agar `crops[]` dan `active_cycle` (plant_name/phase/progress) terisi.
     *
     * @return array<string, mixed>
     */
    private function landEagerLoad(): array
    {
        return [
            'Farmer',
            'Cycles' => fn ($q) => $q->whereHas(
                'Status',
                fn ($s) => $s->where('name', 'Active')->where('type', 'cycle')
            )->with(['Crop', 'Phases.Stage', 'Phases.Status'])
                ->latest('start_date'),
        ];
    }

    /**
     * POST /api/myfields  (Bearer, multipart/form-data)
     *
     * Membuat lahan baru milik user yang sedang login.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'boundary' => ['nullable', 'json'], // string JSON [[lat,lng], ...] dari multipart
            'thumbnail' => ['nullable', 'image', 'max:5120'], // maks 5 MB
        ]);

        $imageUrl = null;
        if ($request->hasFile('thumbnail')) {
            // Simpan ke disk public: storage/app/public/lands -> /storage/lands/...
            $path = $request->file('thumbnail')->store('lands', 'public');
            $imageUrl = '/storage/'.$path;
        }

        // Decode string JSON -> array; cast 'array' di model akan meng-encode ulang saat simpan.
        $boundary = isset($validated['boundary'])
            ? json_decode($validated['boundary'], true)
            : null;

        $land = Land::create([
            'farmer_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'area' => $validated['area'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'boundary' => $boundary,
            'image_url' => $imageUrl,
        ]);

        // Muat relasi agar response konsisten dengan index (owner + crops aktif + active_cycle).
        $land->load($this->landEagerLoad());

        return (new LandResource($land))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/myfields/{id}  (Bearer, multipart; juga POST + _method=PUT)
     *
     * Edit lahan milik user. Owner-check eksplisit: lahan tak ada → 404,
     * milik user lain → 403. Field opsional (sometimes) seperti store.
     * Thumbnail baru menggantikan & menghapus file lama; tanpa file → dipertahankan.
     */
    public function update(Request $request, int $id): LandResource
    {
        $land = $this->ownedLand($request, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'latitude' => ['sometimes', 'nullable', 'numeric'],
            'longitude' => ['sometimes', 'nullable', 'numeric'],
            'boundary' => ['sometimes', 'nullable', 'json'],
            'thumbnail' => ['sometimes', 'nullable', 'image', 'max:5120'], // maks 5 MB
        ]);

        foreach (['name', 'description', 'area', 'latitude', 'longitude'] as $field) {
            if (array_key_exists($field, $validated)) {
                $land->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('boundary', $validated)) {
            $land->boundary = $validated['boundary'] !== null
                ? json_decode($validated['boundary'], true)
                : null;
        }

        // Thumbnail baru: simpan ke disk public lalu hapus file lama. Tanpa file: biarkan.
        if ($request->hasFile('thumbnail')) {
            $this->deleteThumbnail($land);
            $path = $request->file('thumbnail')->store('lands', 'public');
            $land->image_url = '/storage/'.$path;
        }

        $land->save();

        $land->load($this->landEagerLoad());

        return new LandResource($land);
    }

    /**
     * DELETE /api/myfields/{id}  (Bearer)
     *
     * Hapus lahan milik user. Owner-check eksplisit (404/403). Tolak 409 bila
     * lahan masih punya siklus tanam aktif. File thumbnail ikut dihapus.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $land = $this->ownedLand($request, $id);

        $hasActiveCycle = $land->Cycles()
            ->whereHas('Status', fn ($s) => $s->where('name', 'Active')->where('type', 'cycle'))
            ->exists();

        if ($hasActiveCycle) {
            abort(409, 'Lahan masih punya siklus tanam aktif. Selesaikan/hapus siklusnya dulu.');
        }

        $this->deleteThumbnail($land);

        $land->delete();

        return response()->json(['message' => 'Lahan berhasil dihapus']);
    }

    /**
     * Ambil lahan milik user dengan owner-check eksplisit:
     * lahan tak ada → 404, milik user lain → 403.
     */
    private function ownedLand(Request $request, int $id): Land
    {
        $land = Land::find($id);

        if (! $land) {
            abort(404);
        }

        if ($land->farmer_id !== $request->user()->id) {
            abort(403);
        }

        return $land;
    }

    /**
     * Hapus file thumbnail lahan dari disk public bila ada (hindari file yatim).
     */
    private function deleteThumbnail(Land $land): void
    {
        if (! $land->image_url) {
            return;
        }

        // image_url tersimpan sebagai "/storage/lands/...": ubah ke path disk public.
        $path = preg_replace('#^/storage/#', '', $land->image_url);

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
