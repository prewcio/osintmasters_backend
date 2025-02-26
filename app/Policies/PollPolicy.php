<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PollPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Poll $poll)
    {
        return $user->role === 'admin' || $user->id === $poll->created_by;
    }

    public function delete(User $user, Poll $poll)
    {
        return $user->role === 'admin' || $user->id === $poll->created_by;
    }
} 