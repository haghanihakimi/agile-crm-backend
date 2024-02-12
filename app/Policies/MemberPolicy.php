<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MemberPolicy
{
    /**
     * Determine whether the user can delete the model.
     * IF the current user who wants to delete member is member of the current organization
     * And IF the current user who wants to delete member is either Admin or Moderator
     * And IF the targeted member is member of the same organization the admin/moderator is!
     */
    public function delete(User $user, Member $member, $org, $memberUser): Response
    {
        return $user->members()->where('memberable_type', 'App\Models\Organization')
            ->where('memberable_id', $org->id)->exists() &&
            $user->members()->where('memberable_type', 'App\Models\Organization')->where('memberable_id', $org->id)
                ->whereIn('role', ['admin', 'moderator'])->exists() &&
            $memberUser->members()->where('memberable_type', 'App\Models\Organization')
                ->where('memberable_id', $org->id)->exists() &&
            $org->creator_id !== $memberUser->id
            ? Response::allow()
            : Response::deny('You are not able to delete this organization!');
    }
}
