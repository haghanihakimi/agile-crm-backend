<?php

namespace App\Services;

use App\Jobs\OrgInvitationTask;
use App\Models\ActiveSession;
use App\Models\InvitationToken;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class InvitationSerivce
{
    public function acceptInvitation($id, $user)
    {
        $invitation = InvitationToken::with('tokenable:id,org_uuid')->find($id);
        $org = Organization::where('org_uuid', $invitation->tokenable->org_uuid)->first();

        if (!$invitation) {
            return [
                "code" => 404,
                "message" => "Invitation not found!"
            ];
        }
        $joinOrg = $this->joinOrg($org->id, $user);



        if ($joinOrg['code'] === 200) {
            foreach ($org->projects as $project) {
                $joiningProject[] = $this->joinProject($project->id, $user);
                // foreach ($project->tasks as $task) {
                //     $joiningProject[] = $this->joinTask($task->id, $user);
                // }
            }
            $invitation->delete();
            return [
                "code" => 200,
                "message" => "You joind " . $org->name . " organization!",
                "organization" => $org,
            ];
        }

        return [
            "code" => 500,
            "message" => "OOPS! Sorry, something went wrong with joing this organization!",
            "organization" => [],
        ];
    }

    public function rejectInvitation($id)
    {
        $invitation = InvitationToken::with('tokenable:id,org_uuid')->find($id);

        if ($invitation->delete()) {
            return [
                "code" => 200,
                "message" => '',
            ];
        }
        return [
            "code" => 500,
            "message" => 'OOPS! Sorry, something went wrong with rejecting this invitation! Please try again later.',
        ];
    }

    public function joinOrg($orgId, $user)
    {
        $org = Organization::find($orgId);

        if (!$org) {
            return [
                "code" => 404,
                "message" => 'Incorrect organization entry!',
                "member" => [],
            ];
        }

        $member = Member::create([
            'user_id' => $user,
            'memberable_type' => 'App\Models\Organization',
            'memberable_id' => $org->id,
        ]);

        if ($member) {
            return [
                "code" => 200,
                "message" => '',
                "member" => $member,
            ];
        }
        return [
            "code" => 500,
            "message" => 'OOPS! Sorry, something went wrong with joing this organozation. Please try again later.',
            "member" => [],
        ];
    }

    public function joinProject($projectId, $user)
    {
        $project = Project::find($projectId);

        if (!$project) {
            return [
                "code" => 404,
                "message" => "Incorrect project entry!",
                "member" => [],
            ];
        }

        $member = Member::create([
            "user_id" => $user,
            "memberable_type" => "App\Models\Project",
            "memberable_id" => $projectId,
        ]);

        if ($member) {
            return [
                "code" => 200,
                "message" => '',
                "member" => $member,
            ];
        }

        return [
            "code" => 500,
            "message" => "OOPS! Sorry, something went wrong with assigning this project!",
            "member" => [],
        ];
    }

    public function joinTask($taskId, $user)
    {
        $task = Task::find($taskId);

        if (!$task) {
            return [
                "code" => 404,
                "message" => "Incorrect task entry!",
                "member" => [],
            ];
        }

        $member = Member::create([
            "user_id" => $user,
            "memberable_type" => "App\Models\Task",
            "memberable_id" => $taskId,
        ]);

        if ($member) {
            return [
                "code" => 200,
                "message" => '',
                "member" => $member,
            ];
        }

        return [
            "code" => 500,
            "message" => "OOPS! Sorry, something went wrong with assigning this task!",
            "member" => [],
        ];
    }

    public function generateInvitationLink($users)
    {
        if (count($users) > 0) {
            $auth = Auth::guard("api")->user();
            $org = $auth->activeSessions()
                ->where('sessionable_type', 'App\Models\Organization')
                ->with('sessionable:id,org_uuid,name')->first();

            $existingUsers = [];

            if (!$org) {
                return [
                    "code" => 404,
                    "message" => "Incorrect organization entry!",
                    "existing_users" => [],
                ];
            }

            foreach ($users as $user) {
                $existingUser = User::where("email", $user)->first();

                if (!$existingUser) {
                    $user = User::create([
                        "username" => Str::upper(Str::random(16)),
                        "email" => $user
                    ]);
                    OrgInvitationTask::dispatch($auth, $user, $org->sessionable);
                } else {
                    if ($existingUser->members()->where('memberable_id', $org->sessionable->id)->first()) {
                        $existingUsers[] = $existingUser->email;
                    } else {
                        OrgInvitationTask::dispatch($auth, $existingUser, $org->sessionable);
                    }
                }
            }
            return [
                "code" => 200,
                "message" => "",
                "existing_users" => $existingUsers,
            ];
        }

        return [
            "code" => 500,
            "message" => "Please give at least one email address to add new member.",
            "existing_users" => [],
        ];
    }

    public function validateInvitation($email, $signature)
    {
        $invitation = InvitationToken::where("invitee_email", $email)
            ->where("token", $signature)->first();
        if ($invitation) {
            $user = User::where("email", $email)->first();
            // Check if user did not finish signing up process before and the invitation is not expired.
            if (empty($user->firstname) && $invitation->created_at->diffInDays(now()) < 7) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
}