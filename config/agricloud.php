<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambang Batas Stok Menipis (Low Stock Threshold)
    |--------------------------------------------------------------------------
    |
    | Nilai global yang dipakai untuk memicu notifikasi LowStockNotification.
    | Bila stok sebuah Item berada di bawah nilai ini, notifikasi dikirim ke
    | pemilik gudang. Disimpan sebagai konstanta global agar tidak perlu
    | mengubah skema tabel `items` (kolom per-item `min_stock` = enhancement
    | lanjutan).
    |
    */

    'low_stock_threshold' => (int) env('AGRICLOUD_LOW_STOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Masa Berlaku Token (Token TTL)
    |--------------------------------------------------------------------------
    |
    | TTL (dalam menit) untuk token Sanctum yang diterbitkan saat login,
    | register, dan login Google. `default` dipakai untuk sesi biasa
    | (non-remember); `remember` dipakai saat login dengan `remember: true`
    | agar user tidak perlu sering login ulang. Token yang melewati
    | `expires_at` otomatis ditolak guard Sanctum (401).
    |
    */

    'token_ttl' => [
        'default' => (int) env('AGRICLOUD_TOKEN_TTL_DEFAULT', 1440),    // 1 hari
        'remember' => (int) env('AGRICLOUD_TOKEN_TTL_REMEMBER', 43200), // 30 hari
    ],

    /*
    |--------------------------------------------------------------------------
    | Tindakan Hari Ini (Dashboard Tasks/Alerts)
    |--------------------------------------------------------------------------
    |
    | Parameter untuk endpoint computed `GET /api/tasks` (lihat TaskController).
    | Tanpa tabel `tasks` — tindakan diturunkan dari domain (cycles/needs/items).
    |
    | - `harvest_lookahead_days` : jangkauan hari ke depan untuk memunculkan task
    |   panen dari `cycles.end_date` (end_date <= hari ini + N hari).
    | - `urgent_window_days`     : ambang hari untuk menandai urgency `urgent`
    |   (selisih hari ke due_date <= nilai ini, tetapi belum jatuh tempo).
    |
    */

    'harvest_lookahead_days' => (int) env('AGRICLOUD_HARVEST_LOOKAHEAD_DAYS', 7),

    'urgent_window_days' => (int) env('AGRICLOUD_URGENT_WINDOW_DAYS', 2),

];
