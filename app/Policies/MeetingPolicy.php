<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeetingPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return true; // Allow all authenticated users to create meetings
    }

    public function update(User $user, Meeting $meeting)
    {
        return $user->role === 'admin' || $user->id === $meeting->created_by;
    }

    public function delete(User $user, Meeting $meeting)
    {
        return $user->role === 'admin' || $user->id === $meeting->created_by;
    }
} 