<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActiveSessionsRequest;
use App\Http\Requests\MemberRequest;
use App\Models\InvitationToken;
use App\Services\SessionsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Jobs\OrgInvitationTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    private $sessionsService;

    public function __construct(SessionsService $sessionsService)
    {
        $this->sessionsService = $sessionsService;
    }

    public function createOrganization(Request $request, MemberRequest $memberRequest)
    {
        $validator = Validator::make($request->all(), [
            "name" => ['required', 'string', 'max:255', 'min:3'],
            "description" => ["required", "string", "max:255"],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "message" => $validator->errors(),
                "organization" => []
            ]);
        }

        $user = Auth::guard("api")->user();

        $organization = Organization::create([
            "org_uuid" => Str::uuid(),
            "creator_id" => $user->id,
            "name" => $request->input("name"),
            "description" => $request->input("description"),
        ]);


        $memberRequest->store([
            "user_id" => Auth::guard("api")->user()->id,
            "type" => 'App\Models\Organization',
            "id" => $organization->id,
            "role" => 'admin',
        ]);

        return response()->json([
            "code" => 200,
            "message" => '',
            "organization" => $organization
        ]);
    }

    public function getActiveOrg()
    {
        $user = Auth::guard('api')->user();
        $org = $user->activeSessions()
            ->where('sessionable_type', 'App\Models\Organization')
            ->with('sessionable:id,org_uuid,name')->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry!",
                "organization" => [],
            ]);
        }
        $org = Organization::find($org->sessionable->id);

        return response()->json([
            "code" => 200,
            "message" => '',
            "organization" => $org
        ]);
    }

    public function getOrganization($orgUuid)
    {
        $org = Organization::where("org_uuid", $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "organization" => [],
                "message" => 'Incorrect organization entry.',
                "code" => 404,
            ]);
        }

        if (Gate::inspect('view', [$org, $org->id])->denied()) {
            return response()->json([
                "organization" => [],
                "message" => 'You are not a member of the current organization.',
                "code" => 403,
            ]);
        }

        $this->sessionsService->toggleSession(
            Auth::guard("api")->user()->id,
            'App\Models\Organization',
            $org->id
        );

        return response()->json([
            "organization" => $org,
            "message" => '',
            "code" => 200,
        ]);
    }

    public function getOrganizations()
    {
        $user = Auth::guard("api")->user();
        $memberables = $user->members()->where('memberable_type', 'App\Models\Organization')->pluck('memberable_id');

        if (count($memberables) <= 0) {
            return response()->json([
                "code" => 404,
                "organizations" => [],
                "message" => 'Organization not found.',
            ]);
        }

        $organizations = Organization::whereIn('id', $memberables)->get();

        if (count($organizations) <= 0) {
            return response()->json([
                "code" => 404,
                "organizations" => [],
                "message" => 'Organization not found.',
            ]);
        }

        return response()->json([
            "code" => 200,
            "organizations" => $organizations,
            "message" => '',
        ]);
    }

    public function updateOrganization(Request $request, $orgUuid)
    {
        $organization = Organization::where("org_uuid", $orgUuid)->first();

        if (!$organization) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry.",
                "organization" => [],
            ], 404);
        }
        $orgModifyPermission = Gate::inspect('update', [$organization, $organization->id]);

        if ($orgModifyPermission->denied()) {
            return response()->json([
                "code" => 403,
                "message" => "You are not allowed to modify this current organization!",
                "organization" => [],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'min:3'],
            "description" => ["nullable", "string", "max:500"],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "message" => $validator->errors(),
                "organization" => [],
            ], 422);
        }

        $organization->name = $request->name;
        $organization->description = $request->description;
        $organization->save();

        return response()->json([
            "code" => 200,
            "message" => "Organization sucessfully updated.",
            "organization" => $organization,
        ], 200);
    }

    public function deleteOrganization($orgUuid)
    {
        $organization = Organization::where("org_uuid", $orgUuid)->first();
        $user = Auth::guard('api')->user();

        if (!$organization) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry.",
                "organization" => [],
                "sessions" => $user->activeSessions()->count(),
            ], 404);
        }

        $orgModifyPermission = Gate::inspect('delete', [$organization, $organization->id]);

        if ($orgModifyPermission->denied()) {
            return response()->json([
                "code" => 403,
                "message" => "You cannot delete this organization.",
                "organization" => [],
                "sessions" => $user->activeSessions()->count(),
            ], 403);
        }

        $organization->members->each->delete();
        $organization->activeSessions->each->delete();

        $organization->projects->each(function ($project) {
            $project->tasks->each(function ($task) {
                $task->members->each->delete();
                $task->statuses->each->delete();
                $task->priorities->each->delete();
            });
            $project->tasks->each->delete();

            $project->members->each->delete();
            $project->activeSessions->each->delete();
        });

        $organization->projects->each->delete();

        $organization->delete();

        $firstOrganization = Auth::guard('api')->user()->members()
            ->where('memberable_type', 'App\Models\Organization')->first();

        if ($firstOrganization) {
            $this->sessionsService->toggleSession(
                Auth::guard("api")->user()->id,
                'App\Models\Organization',
                $firstOrganization->memberable_id
            );
        }

        return response()->json([
            "code" => 200,
            "message" => $organization->name . " deleted.",
            "organization" => $organization,
            "sessions" => $user->activeSessions()->count(),
        ], 200);
    }
}
