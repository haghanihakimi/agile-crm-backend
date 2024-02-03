<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\InvitationToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\General\OrganizationController;
use App\Services\InvitationSerivce;

class MembersController extends Controller
{
    protected $InvitationSerivce;

    public function __construct(InvitationSerivce $InvitationSerivce)
    {
        $this->InvitationSerivce = $InvitationSerivce;
    }

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

    public function generateInvitationLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "users" => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "message" => $validator->errors(),
                "existing_users" => []
            ]);
        }

        $invite = $this->InvitationSerivce->generateInvitationLink($request->users);

        switch ($invite['code']) {
            case 200:
                return response()->json([
                    "code" => 200,
                    "message" => $invite['message'],
                    "existing_users" => $invite['existing_users']
                ], 200);
            case 404:
                return response()->json([
                    "code" => 404,
                    "message" => $invite['message'],
                    "existing_users" => $invite['existing_users']
                ], 500);
            case 500:
                return response()->json([
                    "code" => 500,
                    "message" => $invite['message'],
                    "existing_users" => $invite['existing_users']
                ], 500);
            default:
                return;
        }
    }

    public function validateInvitation($email, $signature)
    {
        $validation = $this->InvitationSerivce->validateInvitation($email, $signature);

        return response()->json($validation);
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

    public function invitations()
    {
        $user = Auth::guard('api')->user();
        $invitations = InvitationToken::where('invitee_email', $user->email)
            ->with('tokenable:id,org_uuid,name')->get();

        return response()->json([
            "code" => 200,
            "message" => '',
            'invitations' => $invitations->makeHidden(['token', 'tokenable_type', 'tokenable_id', 'created_at', 'updated_at']),
        ], 200);
    }

    public function acceptInvitation($invitationId)
    {
        $user = Auth::guard('api')->user();
        $res = $this->InvitationSerivce->acceptInvitation($invitationId, $user->id);

        if ($res['code'] === 200) {
            return response()->json([
                "code" => 200,
                "message" => $res['message'],
                "organization" => $res['organization'],
            ], 200);
        }
        return response()->json([
            "code" => $res['code'],
            "message" => $res['message'],
            "organization" => $res['organization'],
        ], $res['code']);
    }

    public function rejectInvitation($invitationId)
    {
        $res = $this->InvitationSerivce->rejectInvitation($invitationId);

        if ($res['code'] === 200) {
            return response()->json([
                "code" => 200,
                "message" => $res['message'],
            ], 200);
        }
        return response()->json([
            "code" => $res['code'],
            "message" => $res['message'],
        ], $res['code']);
    }
}
