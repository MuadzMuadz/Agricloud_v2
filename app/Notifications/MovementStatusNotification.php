<?php

namespace App\Notifications;

use App\Models\Movements;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi: persetujuan/perubahan status movement gudang.
 *
 * Class disiapkan untuk scope lanjutan; pemicunya belum dinyalakan di sesi ini.
 */
class MovementStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Movements $movement,
        public string $statusName,
    ) {}

    /**
     * Channel pengiriman.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Payload yang disimpan di tabel `notifications`.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'movement_status',
            'title' => 'Status pergerakan stok',
            'body' => "Pergerakan stok kini berstatus: {$this->statusName}",
            'data' => [
                'movement_id' => $this->movement->id,
                'warehouse_id' => $this->movement->warehouse_id,
                'item_id' => $this->movement->item_id,
                'status_id' => $this->movement->status_id,
                'status' => $this->statusName,
                'quantity' => $this->movement->quantity,
            ],
        ];
    }
}
