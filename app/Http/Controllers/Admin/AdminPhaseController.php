<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Phase;

class AdminPhaseController extends Controller
{
    public function index()
    {
        $phases = Phase::with(['cycle.land.user', 'cycle.crop'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($phases);
    }

    public function show(Phase $phase)
    {
        return response()->json(
            $phase->load(['cycle.land.user', 'cycle.crop'])
        );
    }
}
