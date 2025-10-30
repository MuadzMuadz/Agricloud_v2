<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Items;

class ItemPolicy
{
    /**
     * Admin auto-pass.
     */
    public function before(User $user)
    {
        if ($user->role->name === 'admin') {
            return true;
        }
    }

    /**
     * Lihat item tertentu (hanya kalau warehouse-nya miliknya).
     */
    public function view(User $user, Items $item)
    {
        return $user->id === $item->warehouse->user_id;
    }

    /**
     * Boleh buat item di warehouse-nya sendiri.
     */
    public function create(User $user)
    {
        return $user->role->name === 'farmer' || $user->role->name === 'user';
    }

    /**
     * Update item (hanya kalau warehouse milik sendiri).
     */
    public function update(User $user, Items $item)
    {
        return $user->id === $item->warehouse->user_id;
    }

    /**
     * Delete item.
     */
    public function delete(User $user, Items $item)
    {
        return $user->id === $item->warehouse->user_id;
    }
}
