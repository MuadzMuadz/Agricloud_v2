<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications  (Bearer)
     *
     * Daftar notifikasi milik user login, terbaru dulu, dipaginate.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(15);

        return NotificationResource::collection($notifications);
    }

    /**
     * GET /api/notifications/unread-count  (Bearer)
     *
     * Jumlah notifikasi belum dibaca (untuk badge).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * POST /api/notifications/{id}/read  (Bearer)
     *
     * Tandai satu notifikasi milik user login sebagai dibaca.
     */
    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        // Bungkus eksplisit dalam `data` agar konsisten dengan endpoint lain;
        // wrapper otomatis Laravel dilewati karena resource punya field `data`.
        return response()->json([
            'data' => new NotificationResource($notification->fresh()),
        ]);
    }

    /**
     * POST /api/notifications/read-all  (Bearer)
     *
     * Tandai semua notifikasi belum dibaca milik user login sebagai dibaca.
     */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'data' => [
                'unread_count' => 0,
            ],
        ]);
    }
}
