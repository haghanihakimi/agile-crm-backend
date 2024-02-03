<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActiveSessionsRequest;
use App\Http\Requests\MemberRequest;
use App\Models\InvitationToken;
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


        $memberRequest->store(["user_id" => Auth::guard("api")->user()->id, "type" => 'App\Models\Organization', "id" => $organization->id]);

        return response()->json([
            "code" => 200,
            "message" => '',
            "organization" => $organization
        ]);
    }

    public function getActiveOrg(Organization $organizationModel) {
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

    public function getOrganization($orgUuid, ActiveSessionsRequest $activeSessionsRequest, Organization $organizationModel)
    {
        $org = Organization::where("org_uuid", $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "organization" => [],
                "message" => 'Incorrect organization entry.',
                "code" => 404,
            ]);
        }

        if (Gate::inspect('view', [$organizationModel, $org->id])->denied()) {
            return response()->json([
                "organization" => [],
                "message" => 'You are not a member of the current organization.',
                "code" => 403,
            ]);
        }

        $activeSessionsRequest->store(["user_id" => Auth::guard("api")->user()->id, "type" => 'App\Models\Organization', "id" => $org->id]);

        return response()->json([
            "organization" => $org,
            "message" => '',
            "code" => 200,
        ]);
    }

    public function getOrganizations(Organization $organizationModel)
    {
        $user = Auth::guard("api")->user();

        // $createdOrganizations = Organization::where('creator_id', $user->id)->get();

        $memberOrganizations = Organization::whereIn('id', $user->members()->where('memberable_type', 'App\Models\Organization')->pluck('memberable_id'))->get();

        if (count($memberOrganizations) > 0) {
            // $organizations = $createdOrganizations->merge($memberOrganizations);

            return response()->json([
                "code" => 200,
                "organizations" => $memberOrganizations,
                "message" => '',
            ]);
        }
        return response()->json([
            "code" => 404,
            "organizations" => [],
            "message" => 'Organization not found.',
        ]);
    }

    public function updateOrganization(Request $request, $orgUuid, Organization $organizationModel)
    {
        $organization = Organization::where("org_uuid", $orgUuid)->first();

        if (!$organization) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry.",
                "organization" => [],
            ]);
        }
        $orgModifyPermission = Gate::inspect('update', [$organizationModel, $organization->id]);

        if ($orgModifyPermission->denied()) {
            return response()->json([
                "code" => 403,
                "message" => "You are not a member of the current organization.",
                "organization" => [],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'min:3'],
            "description" => ["nullable", "string"],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "message" => $validator->errors(),
                "organization" => [],
            ]);
        }

        $organization->name = $request->name;
        $organization->description = ($request->description && !empty($request->description)) ?
            $request->description : $organization->description;
        $organization->save();

        return response()->json([
            "code" => 200,
            "message" => "Organization sucessfully updated.",
            "organization" => $organization,
        ]);
    }

    public function deleteOrganization($orgUuid, Organization $organizationModel)
    {
        $organization = Organization::where("org_uuid", $orgUuid)->first();
        $user = Auth::guard('api')->user();

        if (!$organization) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry."
            ]);
        }

        $orgModifyPermission = Gate::inspect('delete', [$organizationModel, $organization->id]);

        if ($orgModifyPermission->denied()) {
            return response()->json([
                "code" => 403,
                "message" => "You are not member of this organization."
            ]);
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

        return response()->json([
            "code" => 200,
            "message" => $organization->name . " deleted.",
        ]);
    }
}
