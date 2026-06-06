<?php

namespace App\Http\Controllers;

use App\Http\Resources\CycleResource;
use App\Models\Cycle;
use App\Models\Phase;
use App\Models\Stage;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CycleController extends Controller
{
    /**
     * GET /api/cycles?field_id={id}  (Bearer)
     *
     * Daftar siklus tanam pada satu lahan milik user. 404 bila lahan bukan milik user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'field_id' => ['required', 'integer'],
        ]);

        // Owner-scoped: findOrFail otomatis 404 bila lahan bukan milik user.
        $land = $request->user()->Lands()->findOrFail($validated['field_id']);

        $cycles = Cycle::where('land_id', $land->id)
            ->with(['Crop', 'Status', 'Phases.Stage', 'Phases.Status'])
            ->latest('start_date')
            ->get();

        return CycleResource::collection($cycles);
    }

    /**
     * POST /api/cycles  (Bearer)
     *
     * Membuat siklus tanam baru ("Mulai Tanam") pada lahan milik user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'land_id' => ['required', 'integer'],
            'crop_id' => ['required', 'integer', 'exists:crops,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:active,pending,done'],
        ]);

        // Owner-scoped: 404 bila lahan bukan milik user.
        $land = $request->user()->Lands()->findOrFail($validated['land_id']);

        // Petakan status kontrak FE -> nama Status DB (type cycle). Default: active.
        $statusName = match ($validated['status'] ?? 'active') {
            'pending' => 'Pending',
            'done' => 'Completed',
            default => 'Active',
        };
        $statusId = Status::where('type', 'cycle')
            ->where('name', $statusName)
            ->value('id');

        $startDate = $validated['start_date'] ?? null;

        // Auto end_date: bila tak dikirim & crop punya stages, turunkan dari
        // total durasi stage (progress butuh start+end). Degrade null bila tak ada.
        $endDate = $validated['end_date'] ?? null;
        if (empty($endDate) && ! empty($startDate)) {
            $totalDurationDays = (int) Stage::where('crop_id', $validated['crop_id'])
                ->sum('duration_days');

            if ($totalDurationDays > 0) {
                $endDate = Carbon::parse($startDate)
                    ->addDays($totalDurationDays)
                    ->toDateString();
            }
        }

        $cycle = DB::transaction(function () use ($validated, $land, $statusId, $startDate, $endDate) {
            $cycle = Cycle::create([
                'land_id' => $land->id,
                'crop_id' => $validated['crop_id'],
                'status_id' => $statusId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // Seed fase awal: stage ber-order terkecil milik crop, Status {Active, phase}.
            // Bila crop tak punya stage → lewati (phase tetap null, FE aman).
            $firstStage = Stage::where('crop_id', $validated['crop_id'])
                ->orderBy('order')
                ->first();

            if ($firstStage !== null) {
                $activePhaseStatusId = Status::where('type', 'phase')
                    ->where('name', 'Active')
                    ->value('id');

                Phase::create([
                    'cycle_id' => $cycle->id,
                    'stage_id' => $firstStage->id,
                    'status_id' => $activePhaseStatusId,
                    'started_at' => now(),
                ]);
            }

            return $cycle;
        });

        $cycle->load(['Crop', 'Status', 'Phases.Stage', 'Phases.Status']);

        return (new CycleResource($cycle))
            ->response()
            ->setStatusCode(201);
    }
}
