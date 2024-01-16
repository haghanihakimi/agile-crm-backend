<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
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
    public function view(User $user, Organization $organization, $orgId): Response
    {
        return $user->members()->where('memberable_type', 'App\Models\Organization')->where('memberable_id', $orgId)->exists()
            ? Response::allow()
            : Response::deny('Join an organization to create project.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function viewAll(User $user, Organization $organization): Response
    {
        return count($user->members()->where('memberable_type', 'App\Models\Organization')->get()) > 0
            ? Response::allow()
            : Response::deny('Join an organization to create project.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization, $orgId): Response
    {
        return $user->members()->where('memberable_type', 'App\Models\Organization')->where('memberable_id', $orgId)->exists()
            ? Response::allow()
            : Response::deny('Join an organization to create project.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization, $orgId): Response
    {
        return $user->members()->where('memberable_type', 'App\Models\Organization')->where('memberable_id', $orgId)->exists()
            ? Response::allow()
            : Response::deny('Join an organization to create project.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organization $organization): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        //
    }
}
