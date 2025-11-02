<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Movements;

class MovementPolicy
{
    /**
     * Admin override semua akses.
     */
    public function before(User $user)
    {
        if ($user->role->name === 'admin') {
            return true;
        }
    }

    /**
     * Lihat movement (hanya yang terkait warehouse miliknya).
     */
    public function view(User $user, Movements $movement)
    {
        return $user->id === $movement->item->warehouse->user_id;
    }

    /**
     * Create movement (stok keluar/masuk).
     */
    public function create(User $user)
    {
        // Farmer bisa buat movement antar warehouse-nya sendiri
        return $user->role->name === 'farmer' || $user->role->name === 'user';
    }

    /**
     * Update movement (biasanya jarang diubah).
     */
    public function update(User $user, Movements $movement)
    {
        return $user->id === $movement->item->warehouse->user_id;
    }

    /**
     * Hapus movement (opsional).
     */
    public function delete(User $user, Movements $movement)
    {
        return $user->id === $movement->item->warehouse->user_id;
    }
}
