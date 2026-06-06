<?php

namespace App\Notifications;

use App\Models\Needs;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi: kebutuhan (Needs) fase yang belum terpenuhi.
 *
 * Class disiapkan untuk scope lanjutan; pemicunya belum dinyalakan di sesi ini.
 */
class NeedsUnfulfilledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Needs $need,
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
            'type' => 'needs_unfulfilled',
            'title' => 'Kebutuhan belum terpenuhi',
            'body' => "Kebutuhan input fase sebanyak {$this->need->quantity_needed} belum terpenuhi",
            'data' => [
                'need_id' => $this->need->id,
                'phase_id' => $this->need->phase_id,
                'item_id' => $this->need->item_id,
                'quantity_needed' => $this->need->quantity_needed,
            ],
        ];
    }
}
