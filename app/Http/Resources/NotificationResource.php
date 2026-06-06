<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk JSON konsisten untuk notifikasi in-app (kontrak web & mobile).
 *
 * Membungkus model bawaan Laravel `Illuminate\Notifications\DatabaseNotification`.
 * Payload `data` notifikasi disepakati berbentuk:
 *   { "type": ..., "title": ..., "body": ..., "data": { ...context } }
 */
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = $this->data;

        return [
            'id' => $this->id,
            'type' => $payload['type'] ?? $this->type,
            'title' => $payload['title'] ?? null,
            'body' => $payload['body'] ?? null,
            'data' => $payload['data'] ?? [],
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
