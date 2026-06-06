<?php

namespace App\Http\Controllers;

use App\Support\TaskDeriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/summary  (Bearer)
     *
     * Ringkasan KPI dashboard farmer. `attention_count` = jumlah tindakan
     * ber-urgency ∈ {due, urgent} (KPI "Perlu Perhatian"), `task_count` = total
     * tindakan hari ini. Memungkinkan FE mengisi KPI dengan satu call.
     */
    public function summary(Request $request): JsonResponse
    {
        $tasks = TaskDeriver::forUser($request->user());

        return response()->json([
            'attention_count' => TaskDeriver::attentionCount($tasks),
            'task_count' => count($tasks),
        ]);
    }
}
