<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Items;
use Illuminate\Support\Facades\Log;

class ItemPolicy
{
    public function view(User $user, Items $item): bool
    {
        $item->loadMissing('warehouse');

        Log::info('Policy check', [
            'user_id' => $user->id,
            'warehouse_owner' => $item->warehouse?->farmer_id,
            'item_id' => $item->id,
            'warehouse_id' => $item->warehouse_id,
        ]);

        return $user->role->name === 'admin' || $item->warehouse->farmer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role->name === 'farmer';
    }

    public function update(User $user, Items $item): bool
    {
        return $item->warehouse->farmer_id === $user->id;
    }

    public function delete(User $user, Items $item): bool
    {
        return $item->warehouse->farmer_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role->name, ['admin', 'farmer']);
    }
}
