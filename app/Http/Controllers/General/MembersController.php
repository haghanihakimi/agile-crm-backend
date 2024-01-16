<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Http\Controllers\General\OrganizationController;

class MembersController extends Controller
{

    public function getOrgMembers($orgUuid, Organization $organizationModel)
    {
        $org = Organization::where('org_uuid', $orgUuid)->first();
        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry!",
                "orgMembers" => [],
            ]);
        }

        $orgGate = Gate::inspect('view', [$organizationModel, $org->id]);

        if ($orgGate->denied()) {
            return response()->json([]);
        }

        $members = $org->members()->with('users')->get()->flatten()->pluck('users')->unique('id');

        return response()->json([
            "code" => 200,
            "message" => "",
            "orgMembers" => $members,
        ]);
    }

    public function getProjectMembers($orgUuid, $projectUuid, Organization $organizationModel, Project $projectModel)
    {
        $org = Organization::where('org_uuid', $orgUuid)->first();
        $project = Project::where('project_uuid', $projectUuid)->first();

        if (!$org || !$project) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect project or organization entry!",
                "projectMembers" => [],
            ]);
        }

        $orgGate = Gate::inspect('view', [$organizationModel, $org->id]);
        $projectGate = Gate::inspect('view', [$projectModel, $project->id]);

        if ($orgGate->denied() || $projectGate->denied()) {
            return response()->json([
                "code" => 403,
                "message" => "You are not a member of the current organization or project!",
                "projectMembers" => [],
            ]);
        }

        $members = $project->members()->with('users')->get()->flatten()->pluck('users')->unique('id');

        return response()->json([
            "code" => 200,
            "message" => "",
            "projectMembers" => $members,
        ]);
    }

    public function getTaskMembers($orgUuid, $projectUuid, $taskUuid, Organization $organizationModel, Project $projectModel)
    {
        $org = Organization::where("org_uuid", $orgUuid)->first();
        $project = Project::where("project_uuid", $projectUuid)->first();
        $task = Task::where("task_uuid", $taskUuid)->first();

        if (!$org || !$project || !$task) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect project, organization or task entry!",
                "taskMembers" => [],
            ]);
        }

        $orgGate = Gate::inspect('view', [$organizationModel, $org->id]);
        $projectGate = Gate::inspect('view', [$projectModel, $project->id]);

        if ($orgGate->denied() || $projectGate->denied()) {
            return response()->json([
                "code" => 403,
                "message" => "You are not a member of the current project or organization",
                "taskMembers" => [],
            ]);
        }

        $members = $task->with('members.users')->get()->pluck('members')->flatten()->pluck('users')->unique('id');

        return response()->json([
            "code" => 200,
            "message" => "",
            "taskMembers" => $members,
        ]);
    }

    public function inviteMembers(Request $request)
    {
        switch ($request->inviteTo) {
            case "Organization":
                $organization = new OrganizationController();
                $invitation = $organization::generateInvitationLink($request->users);
                return response()->json($invitation);
            default:
                return response()->json("broke");
        }
    }
}
