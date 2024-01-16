<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
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
    public function view(User $user, Project $project, $pId): Response
    {
        return $user->members()->where('memberable_type', 'App\Models\Project')->where('memberable_id', $pId)->exists()
            ? Response::allow()
            : Response::deny('Join an organization to create project.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function modify(User $user, Project $project, $orgId): Response
    {
        return $user->members()->where('memberable_type', 'App\Models\Organization')->where('memberable_id', $orgId)->exists()
            ? Response::allow()
            : Response::deny('Join an organization to create project.');
    }

    /**
     * Determine whether the user can switch the current models.
     */
    public function switch (User $user, Project $project, $pId): Response
    {
        return $user->members()->where('memberable_type', 'App\Models\Project')->where('memberable_id', $pId)->exists()
            ? Response::allow()
            : Response::deny('Join an organization to create project.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        //
    }
}
