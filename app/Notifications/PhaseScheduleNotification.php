<?php

namespace App\Notifications;

use App\Models\Phase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi: jadwal fase/panen (perubahan atau peringatan fase pada Cycle/Phase).
 *
 * Class disiapkan untuk scope lanjutan; pemicunya belum dinyalakan di sesi ini.
 */
class PhaseScheduleNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Phase $phase,
        public string $message,
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
            'type' => 'phase_schedule',
            'title' => 'Jadwal fase',
            'body' => $this->message,
            'data' => [
                'phase_id' => $this->phase->id,
                'cycle_id' => $this->phase->cycle_id,
                'stage_id' => $this->phase->stage_id,
                'started_at' => $this->phase->started_at,
                'ended_at' => $this->phase->ended_at,
            ],
        ];
    }
}
