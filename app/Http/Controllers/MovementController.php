<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovementResource;
use App\Models\Items;
use App\Models\Movements;
use App\Models\MoveTypes;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovementController extends Controller
{
    /**
     * GET /api/warehouses/{id}/movements  (Bearer)
     *
     * Riwayat transaksi (movements) satu gudang milik user yang sedang login,
     * terbaru dulu. 404 (owner-scoped) bila gudang bukan milik user.
     */
    public function index(Request $request, int $id): AnonymousResourceCollection
    {
        $warehouse = $request->user()
            ->Warehouses()
            ->findOrFail($id);

        $movements = $warehouse->Movements()
            ->with(['Item.Category', 'Movetype', 'Status'])
            ->latest()
            ->latest('id') // tiebreaker bila created_at sama (transaksi 1 detik)
            ->get();

        return MovementResource::collection($movements);
    }

    /**
     * POST /api/movements  (Bearer)
     *
     * Mencatat transaksi masuk (IN) / keluar (OUT) yang otomatis menyesuaikan
     * `items.stock` secara atomik. OUT divalidasi agar stok tak minus (422).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'type' => ['required', 'string', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'note' => ['nullable', 'string'],
        ]);

        // Owner-scope: item harus berada di gudang milik user yang login.
        $item = Items::whereHas('Warehouse', function ($query) use ($request) {
            $query->where('farmer_id', $request->user()->id);
        })->findOrFail($validated['item_id']);

        $code = strtoupper($validated['type']); // IN | OUT
        $quantity = $validated['quantity'];

        $movement = DB::transaction(function () use ($item, $code, $quantity, $validated) {
            $movetype = MoveTypes::where('code', $code)->firstOrFail();
            $statusId = Status::where('type', 'movement')->where('name', 'Done')->value('id');

            if ($code === 'OUT') {
                if ($item->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => ['Stok tidak cukup.'],
                    ]);
                }
                $item->decrement('stock', $quantity);
            } else {
                $item->increment('stock', $quantity);
            }

            return Movements::create([
                'warehouse_id' => $item->warehouse_id,
                'item_id' => $item->id,
                'movetype_id' => $movetype->id,
                'status_id' => $statusId,
                'quantity' => $quantity,
                'note' => $validated['note'] ?? null,
            ]);
        });

        $movement->load(['Item.Category', 'Movetype', 'Status']);

        return (new MovementResource($movement))
            ->response()
            ->setStatusCode(201);
    }
}
