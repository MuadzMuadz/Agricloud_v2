<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cycle;

class AdminCycleController extends Controller
{
    public function index()
    {
        $cycles = Cycle::with(['land.user', 'crop', 'phases'])
            ->latest()
            ->get();

        return response()->json($cycles);
    }

    public function show(Cycle $cycle)
    {
        return response()->json(
            $cycle->load(['land.user', 'crop', 'phases'])
        );
    }
}
