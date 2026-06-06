<?php

namespace Database\Seeders;

use App\Models\Items;
use App\Models\Movements;
use App\Models\Needs;
use App\Models\Phase;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\MovementStatusNotification;
use App\Notifications\NeedsUnfulfilledNotification;
use App\Notifications\PhaseScheduleNotification;
use Illuminate\Database\Seeder;

/**
 * Mengisi notifikasi dummy untuk memudahkan pengembangan & tes web (Notif-Web).
 *
 * Mengirim beberapa notifikasi (semua jenis) ke beberapa user pertama agar
 * endpoint list/unread-count punya data nyata untuk dikonsumsi dashboard.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::limit(3)->get();

        if ($users->isEmpty()) {
            $this->command->warn('⚠️ Tidak ada user. Jalankan UserSeeder dulu.');

            return;
        }

        $threshold = (int) config('agricloud.low_stock_threshold');
        $item = Items::first();
        $phase = Phase::first();
        $movement = Movements::first();
        $need = Needs::first();

        foreach ($users as $user) {
            if ($item) {
                $user->notify(new LowStockNotification($item, $threshold));
            }
            if ($phase) {
                $user->notify(new PhaseScheduleNotification($phase, 'Fase memasuki tahap berikutnya.'));
            }
            if ($movement) {
                $user->notify(new MovementStatusNotification($movement, 'Disetujui'));
            }
            if ($need) {
                $user->notify(new NeedsUnfulfilledNotification($need));
            }
        }

        $this->command->info('✅ NotificationSeeder: notifikasi dummy untuk '.$users->count().' user berhasil dibuat.');
    }
}
