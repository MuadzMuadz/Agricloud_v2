<?php

namespace App\Observers;

use App\Models\Items;
use App\Notifications\LowStockNotification;

/**
 * Pemicu otomatis notifikasi stok menipis.
 *
 * Saat sebuah Item dibuat/diperbarui dan stoknya turun di bawah ambang
 * `config('agricloud.low_stock_threshold')`, kirim LowStockNotification ke
 * pemilik (farmer) gudang terkait.
 */
class ItemsObserver
{
    public function created(Items $item): void
    {
        $this->notifyIfLowStock($item);
    }

    public function updated(Items $item): void
    {
        // Hanya picu bila stok memang berubah pada operasi ini.
        if ($item->wasChanged('stock')) {
            $this->notifyIfLowStock($item);
        }
    }

    private function notifyIfLowStock(Items $item): void
    {
        $threshold = (int) config('agricloud.low_stock_threshold');

        if ($item->stock >= $threshold) {
            return;
        }

        $farmer = $item->Warehouse?->Farmer;

        $farmer?->notify(new LowStockNotification($item, $threshold));
    }
}
