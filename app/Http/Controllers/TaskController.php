<?php

namespace App\Http\Controllers;

use App\Support\TaskDeriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * GET /api/tasks?due=today  (Bearer)
     *
     * "Tindakan Hari Ini" — daftar tindakan yang diturunkan (computed) dari domain
     * milik user (cycles/needs/items), tanpa tabel `tasks`. Lihat
     * [[Dashboard-TasksAlerts-Backend]] arah A-lite.
     *
     * Param `due` saat ini hanya bernilai `today` (default) — disediakan untuk
     * kompatibilitas kontrak FE; tanpa data → `{"data": []}`.
     */
    public function index(Request $request): JsonResponse
    {
        $tasks = TaskDeriver::forUser($request->user());

        return response()->json(['data' => $tasks]);
    }
}
