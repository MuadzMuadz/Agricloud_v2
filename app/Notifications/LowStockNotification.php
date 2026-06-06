<?php

namespace App\Notifications;

use App\Models\Items;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi: stok gudang menipis (Items.stock di bawah ambang batas).
 */
class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Items $item,
        public int $threshold,
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
            'type' => 'low_stock',
            'title' => 'Stok menipis',
            'body' => "{$this->item->name} tersisa {$this->item->stock} {$this->item->unit}",
            'data' => [
                'item_id' => $this->item->id,
                'warehouse_id' => $this->item->warehouse_id,
                'stock' => $this->item->stock,
                'unit' => $this->item->unit,
                'threshold' => $this->threshold,
            ],
        ];
    }
}
