<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaterialPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Material $material)
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Material $material)
    {
        return $user->role === 'admin';
    }
} 