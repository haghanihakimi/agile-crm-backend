<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Task $task, $orgId, $pId): Response
    {
        return $user->members()->where('memberable_type', 'App\Models\Organization')->where('memberable_id', $orgId)->exists() &&
        $user->members()->where('memberable_type', 'App\Models\Project')->where('memberable_id', $pId)->exists()
            ? Response::allow()
            : Response::deny('Invalid project or organization entries.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task, $orgId, $pId): Response
    {
        return $user->members()->where('memberable_type', 'App\Models\Organization')->where('memberable_id', $orgId)->exists() &&
        $user->members()->where('memberable_type', 'App\Models\Project')->where('memberable_id', $pId)->exists()
            ? Response::allow()
            : Response::deny('Invalid project or organization entries.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        //
    }
}
