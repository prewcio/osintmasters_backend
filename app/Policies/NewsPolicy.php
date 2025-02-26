<?php

namespace App\Policies;

use App\Models\News;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(?User $user, string $ability)
    {
        if ($user && $user->role === 'admin') {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return true; // All authenticated users can view news
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, News $news)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, News $news)
    {
        return $user->role === 'admin' || $news->author === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, News $news)
    {
        return $user->role === 'admin';
    }
} 