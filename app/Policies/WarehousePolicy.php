<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    /**
     * Admin auto-override.
     */
    public function before(User $user)
    {
        if ($user->role->name === 'admin') {
            return true;
        }
    }

    /**
     * Lihat daftar / detail warehouse.
     */
    public function view(User $user, Warehouse $warehouse)
    {
        return $user->id === $warehouse->farmer_id;
    }

    /**
     * Boleh buat warehouse baru.
     */
    public function create(User $user)
    {
        // Semua farmer/user biasa boleh create warehouse
        return $user->role->name === 'farmer';
    }

    /**
     * Update warehouse (hanya miliknya).
     */
    public function update(User $user, Warehouse $warehouse)
    {
        return $user->id === $warehouse->farmer_id;
    }

    /**
     * Hapus warehouse (hanya miliknya).
     */
    public function delete(User $user, Warehouse $warehouse)
    {
        return $user->id === $warehouse->farmer_id;
    }
}
