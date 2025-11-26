<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Movements;

class MovementPolicy
{
    public function view(User $user, Movements $movement): bool
    {
        $movement->loadMissing('warehouse');
        return $movement->warehouse && $movement->warehouse->farmer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'farmer';
    }

    public function update(User $user, Movements $movement): bool
    {
        $movement->loadMissing('warehouse');
        return $movement->warehouse && $movement->warehouse->farmer_id === $user->id;
    }

    public function delete(User $user, Movements $movement): bool
    {
        $movement->loadMissing('warehouse');
        return $movement->warehouse && $movement->warehouse->farmer_id === $user->id;
    }
}
