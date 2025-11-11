<?php

namespace App\Policies;

use App\Models\{User, Cycle};

class CyclePolicy
{
    public function view(User $user, Cycle $cycle): bool
    {
        return $cycle->land && $cycle->land->farmer_id === $user->id;
    }

    public function update(User $user, Cycle $cycle): bool
    {
        return $cycle->land && $cycle->land->farmer_id === $user->id;
    }

    public function delete(User $user, Cycle $cycle): bool
    {
        return $cycle->land && $cycle->land->farmer_id === $user->id;
    }
}
