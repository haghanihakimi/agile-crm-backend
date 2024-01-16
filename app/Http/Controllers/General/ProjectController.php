<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActiveSessionsRequest;
use App\Http\Requests\MemberRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Member;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function getProjects($orgUuid, Organization $organizationModel)
    {
        $organization = Organization::where("org_uuid", $orgUuid)->first();

        if (!$organization) {
            return response()->json([
                "code" => 200,
                "allProjects" => [],
                "message" => ''
            ]);
        }

        $user = Auth::guard('api')->user();
        $orgMember = Gate::inspect('view', [$organizationModel, $organization->id]);

        if ($orgMember->denied()) {
            return response()->json([
                "code" => 200,
                "allProjects" => [],
                "message" => ''
            ]);
        }

        $projects = $organization->projects()->where(function ($query) use ($user) {
            $query->where('private', false)
                ->orWhere('creator_id', $user->id);
        })->get();

        return response()->json([
            "code" => 200,
            "allProjects" => $projects,
            "message" => ''
        ]);



        // if (Gate::inspect('view', $organizationModel)->allowed()) {
        //     $user = Auth::guard('api')->user();
        //     $activeOrg = $user->activeSessions()->where('sessionable_type', 'App\Models\Organization')->first();
        //     $organization = Organization::find($activeOrg->sessionable_id);

        //     $projects = $organization->projects()->where(function ($query) use ($user) {
        //         $query->where('private', false)
        //             ->orWhere('creator_id', $user->id);
        //     })->get();

        //     return response()->json([
        //         "projects" => $projects,
        //     ]);
        // }
        // return response()->json([]);
    }

    public function currentProject($orgUuid, $projectUuid, Project $projectModel, Organization $organizationModel)
    {
        $org = Organization::where("org_uuid", $orgUuid)->first();
        $user = Auth::guard('api')->user();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "project" => [],
                "message" => 'Incorrect organization or project entry!',
            ]);
        }
        
        $project = $org->projects()->where("project_uuid", $projectUuid)->first();
        if (!$project) {
            return response()->json([
                "code" => 404,
                "project" => [],
                "message" => 'Incorrect organization or project entry!'
            ]);
        }

        $projectMember = Gate::inspect('view', [$projectModel, $project->id]);
        $orgMember = Gate::inspect('view', [$organizationModel, $org->id]);

        if ($projectMember->denied() && $orgMember->denied()) {
            return response()->json([
                "code" => 403,
                "project" => [],
                "message" => 'You are not a member of the current organization or project!'
            ]);
        }

        return response()->json([
            "code" => 200,
            "project" => (!$project->private || $project->creator_id === $user->id) ? $project : [],
            "message" => '',
        ]);
    }

    public function createProject(Request $request, $orgUuid, Organization $organizationModel, Project $projectModel, MemberRequest $memberRequest, ActiveSessionsRequest $activeSessionsRequest)
    {
        $org = Organization::where('org_uuid', $orgUuid)->first();
        
        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry!",
                "newProject" => []
            ]);
        }

        // Check if the user is allowed to create a project in the organization
        $projectCreationPermission = Gate::inspect('modify', [$projectModel, $org->id]);

        if ($projectCreationPermission->denied()) {
            return response()->json([
                "code" => 403,
                "newProject" => [],
                "message" => "Please join an organization to create projects."
            ]);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            "title" => ['required', 'string', 'max:255', 'min:3'],
            "description" => ["required", "string", "max:128"],
            "workplace" => ["nullable"],
            "privacy" => ["required", "boolean"],
            "budget" => ["nullable", 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "newProject" => [],
                "message" => $validator->errors(),
            ]);
        }
        $user = Auth::guard('api')->user();

        // Create a new project
        $project = Project::create([
            "project_uuid" => Str::uuid(),
            "creator_id" => $user->id,
            "title" => $request->input("title"),
            "description" => $request->input("description"),
            "private" => $request->input("privacy"),
            "budget" => $request->input("budget"),
        ]);

        // Attach the project to the organization
        $project->organizations()->attach($org->id, ['created_at' => now(), 'updated_at' => now()]);

        // Store the member request
        $memberRequest->store(["user_id" => $user->id, "type" => 'App\Models\Project', "id" => $project->id]);

        return response()->json([
            "code" => 200,
            "message" => "",
            "newProject" => $project,
        ]);
    }

    public function updateProject(Request $request, $orgUuid, $projectUuid, Organization $organizationModel, Project $projectModel)
    {
        $org = Organization::where('org_uuid', $orgUuid)->first();

        // Check if the organization and project exist
        if (!$org) {
            return response()->json([
                "code" => 404,
                "project" => [],
                "message" => 'Incorrect project or organization entry!',
            ]);
        }

        $project = $org->projects()->where('project_uuid', $projectUuid)->first();
        // Check if the project exist
        if (!$project) {
            return response()->json([
                "code" => 404,
                "project" => [],
                "message" => 'Incorrect project or organization entry!',
            ]);
        }

        // Check if the user has permission to modify the project and view the organization
        $projectModifyPermission = Gate::inspect('view', [$projectModel, $project->id]);
        $orgMember = Gate::inspect('view', [$organizationModel, $org->id]);

        if ($projectModifyPermission->denied() && $orgMember->denied()) {
            return response()->json([
                "code" => 403,
                "project" => [],
                "message" => 'You are not a member of the current organization or project.',
            ]);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            "title" => ['required', 'string', 'max:255', 'min:3'],
            "description" => ["required", "string", "max:255"],
            "private" => ["required", "boolean"],
            "budget" => ["required", 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "project" => [],
                "message" => $validator->errors(),
            ]);
        }

        // Update the project attributes
        $project->update([
            "title" => $request->title,
            "description" => $project->description === $request->description ? $project->description : $request->description,
            "private" => $request->private,
            "budget" => $request->budget,
        ]);

        return response()->json([
            "code" => 200,
            "project" => $project,
            "message" => 'Project successfully updated.',
        ]);
    }

    public function deleteProject($orgUuid, $projectUuid, Organization $organizationModel, Project $projectModel)
    {
        $org = Organization::where("org_uuid", $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => 'Incorrect organization or project entry!',
                "deletedProject" => [],
            ]);
        }

        $project = $org->projects()->where('project_uuid', $projectUuid)->first();
        if (!$project) {
            return response()->json([
                "code" => 404,
                "message" => 'Incorrect organization or project entry!',
                "deletedProject" => [],
            ]);
        }

        $projectModifyPermission = Gate::inspect('view', [$projectModel, $project->id]);
        $orgMember = Gate::inspect('view', [$organizationModel, $org->id]);

        if ($projectModifyPermission->denied() && $orgMember->denied()) {
            return response()->json([
                "code" => 403,
                "message" => 'You are not a member of the current project or organization.',
                "deletedProject" => [],
            ]);
        }

        // Delete all connected task members, task statuses and task priorities first
        $project->tasks->each(function ($task) {
            $task->members->each->delete();
            $task->statuses->each->delete();
            $task->priorities->each->delete();
        });

        // Then delete all tasks, project members and project active sessions
        $project->tasks->each->delete();
        $project->members->each->delete();
        $project->activeSessions->each->delete();

        $project->delete();


        return response()->json([
            "code" => 200,
            "message" => $project->title . " deleted.",
            "deletedProject" => [],
        ]);
    }

    public static function addMembers(Request $request, $projectUuid, $orgUuid)
    {
        $project = Project::where('project_uuid', $projectUuid)->first();
        $org = $project->organizations()->where('org_uuid', $orgUuid)->first();
        $user = User::where('email', $request->email)->first();

        if (!$project->members()->where('user_id', $user->id)->exists()) {
            if ($org->members()->where('user_id', $user->id)->exists()) {
                $member = Member::create([
                    "user_id" => $user->id,
                    "memberable_id" => $project->id,
                    "memberable_type" => "App\Models\Project",
                ]);

                return response()->json([
                    "message" => $user->firstname . " " . $user->lastname . " successfully added to " . $project->title . ".",
                ]);
            }
            return response()->json([
                "message" => $user->firstname . " " . $user->lastname . " is not member of the current organization. Please invite them to the current organization."
            ]);
        }
        return response()->json([
            "message" => "Invalid request!",
        ]);
    }

    public function totalProjects($orgUuid, Organization $organization)
    {
        $user = Auth::guard('api')->user();
        $org = Organization::where("org_uuid", $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry!",
                "projectsCount" => 0,
            ]);
        }

        if (Gate::inspect('view', [$organization, $org->id])->denied()) {
            return response()->json([
                "code" => 403,
                "message" => "You are not a member of the current organization.",
                "projectsCount" => 0,
            ]);
        }

        $totalProjects = $org->projects()->count();

        return response()->json([
            "code" => 200,
            "message" => "",
            "projectsCount" => $totalProjects,
        ]);
    }

    public function showProject($orgUuid, $projectUuid, Organization $organizationModel, Project $projectModel) {
        $org = $organizationModel->where('org_uuid', $orgUuid)->first();
        $project = $projectModel->where('project_uuid', $projectUuid)->first();

        if (!$org || !$project) {
            return response()->json([
                "code" => 404,
                "message" => 'Incorrect project or organization entry!',
                "project" => [],
            ]);
        }

        $projectPermission = Gate::inspect('view', [$projectModel, $project->id]);
        $orgPermission = Gate::inspect('view', [$organizationModel, $org->id]);
        if ($orgPermission->denied() || $projectPermission->denied()) {
            return response()->json([
                "code" => 403,
                "message" => 'You are not a member of the current project or organization!',
                "project" => [],
            ]);
        }
        
        return response()->json([
            "code" => 200,
            "message" => '',
            "project" => $project,
        ]);
    }
}
