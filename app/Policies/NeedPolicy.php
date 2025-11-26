<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Needs;

class NeedPolicy
{
    /**
     * Admin auto-pass semua akses.
     */
    public function before(User $user)
    {
        if ($user->role->name === 'admin') {
            return true;
        }
    }

    /**
     * Lihat kebutuhan (need) — hanya kebutuhan dari item warehouse miliknya.
     */
    public function view(User $user, Needs $need)
    {
        return $user->id === $need->item->warehouse->farmer_id;
    }

    /**
     * Buat need baru (request item untuk cycle stage).
     */
    public function create(User $user)
    {
        return $user->role->name === 'farmer';
    }

    /**
     * Update need (misal ubah status fulfilled).
     */
    public function update(User $user, Needs $need)
    {
        return $user->id === $need->item->warehouse->farmer_id;
    }

    /**
     * Delete need (optional).
     */
    public function delete(User $user, Needs $need)
    {
        return $user->id === $need->item->warehouse->farmer_id;
    }
}

