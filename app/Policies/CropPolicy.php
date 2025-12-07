<?php

namespace App\Policies;

use App\Models\Crop;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CropPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role->name === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Crop $crop): bool
    {
        return $user->role->name === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Crop $crop): bool
    {
        return $user->role->name === 'admin';
    }

}
