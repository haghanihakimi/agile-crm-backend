<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Requests\EditsRequest;
use App\Models\Organization;
use App\Models\User;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\Member;
use App\Services\TaskFileHandlerService;
use App\Services\TaskUpdaterService;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\MemberRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    protected $taskUpdaterService;
    protected $taskFileHandlerService;

    public function __construct(TaskUpdaterService $taskUpdaterService, TaskFileHandlerService $taskFileHandlerService)
    {
        $this->taskUpdaterService = $taskUpdaterService;
        $this->taskFileHandlerService = $taskFileHandlerService;
    }

    public function create(Request $request, $orgUuid, $projectUuid, MemberRequest $memberRequest, Task $taskModel, EditsRequest $editsRequest)
    {
        $org = Organization::where('org_uuid', $orgUuid)->first();
        $project = Project::where('project_uuid', $projectUuid)->first();

        if (!$org || !$project) {
            return response()->json([
                "newTask" => [],
                "code" => 404,
                "message" => "Incorrect project or organization entry!"
            ]);
        }

        $validator = Validator::make($request->all(), [
            "name" => ["required", "string", "max:255", "min:5"],
            "taskAssignees" => ["array", "required", "min:1"],
            "description" => ["required", "string", "max:5000"],
            "priority" => ["array", "required", "min:1"],
            "status" => ["array", "required", "min:1"],
            "privacy" => ["required", "boolean"],
            "dueDate" => ["nullable", "string"],
        ]
        );

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "newTask" => [],
                "message" => $validator->errors(),
            ]);
        }

        $user = Auth::guard('api')->user();
        $canCreate = Gate::inspect('create', [$taskModel, $org->id, $project->id]);

        if ($canCreate->denied()) {
            return response()->json([
                "code" => 403,
                "newTask" => [],
                "message" => "You are not a member of the current project or organization!",
            ]);
        }

        $task = Task::create([
            "task_uuid" => Str::uuid(),
            "creator_id" => $user->id,
            "title" => $request->input('name'),
            "description" => $request->input("description"),
            "due_date" => Carbon::parse($request->input("dueDate"))->format("Y-m-d H:i:s"),
            "private" => $request->input('privacy'),
        ]);

        if (!$task) {
            return response()->json([
                "code" => 500,
                "newTask" => [],
                "message" => "Creating new task failed! Please try again later.",
            ]);
        }

        $task->projects()->attach($project->id);

        foreach ($request->priority as $priority) {
            $newPriority = Priority::create([
                "name" => $priority['title'],
                "is_selected" => $priority['is_selected']
            ]);
            $newPriority->tasks()->attach($task->id);
        }

        foreach ($request->status as $status) {
            $newStatus = Status::create(["name" => $status['title'], 'is_selected' => $status['is_selected']]);
            $newStatus->tasks()->attach($task->id);
        }

        foreach ($request->taskAssignees as $email) {
            $assignee = User::where('email', $email)->first();
            $memberRequest->store(["user_id" => $assignee->id, "type" => 'App\Models\Task', "id" => $task->id]);
        }
        $editsRequest->store(["edited_by" => $user->id, "type" => 'App\Models\Task', "id" => $task->id]);

        return response()->json([
            "newTask" => $task->load(['statuses', 'priorities', 'members.users', 'comments.users', 'files', 'edits.users']),
            "code" => 200,
            "message" => ""
        ]);
    }

    public function getTask(Project $projectModel, Organization $organizationModel, $taskUuid)
    {
        $projectGate = Gate::inspect('view', $projectModel);
        $organizationGate = Gate::inspect('view', $organizationModel);

        if ($projectGate->allowed() && $organizationGate->allowed()) {
            $task = Task::where("task_uuid", $taskUuid)->first();

            return response()->json([
                "task" => $task,
                "assignees" => $task->members()->with("users")->get(),
                "files" => $task->files,
            ]);
        }
    }

    public function getTasks($orgUuid, $projectUuid, Project $projectModel, Organization $organizationModel)
    {
        $org = Organization::where('org_uuid', $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "todoTasks" => [],
                "message" => 'Incorrect project or organization entry!',
            ]);
        }

        $project = $org->projects()->where('project_uuid', $projectUuid)->first();
        if (!$project) {
            return response()->json([
                "code" => 404,
                "todoTasks" => [],
                "message" => 'Incorrect project or organization entry!',
            ]);
        }

        $projectGate = Gate::inspect('view', [$projectModel, $project->id]);
        $orgGate = Gate::inspect('view', [$organizationModel, $org->id]);
        if ($projectGate->denied() || $orgGate->denied()) {
            return response()->json([
                "code" => 403,
                "todoTasks" => [],
                "message" => 'You are not a member of the current project or organization.',
            ]);
        }

        $tasks = $project->tasks()->with(['statuses', 'priorities', 'members.users', 'comments.users', 'files', 'edits.users'])->get();

        return response()->json([
            "code" => 200,
            "todoTasks" => $tasks,
            "message" => '',
        ]);
    }

    public function updateTask(Request $request, $orgUuid, $projectIuid, $taskUuid, Task $taskModel)
    {

        $org = Organization::where("org_uuid", $orgUuid)->first();
        $project = Project::where("project_uuid", $projectIuid)->first();
        $task = Task::where("task_uuid", $taskUuid)->first();

        if (!$org || !$project || !$task) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect task, project or organization entries.",
                "task" => [],
            ]);
        }
        

        $validation = $this->taskUpdaterService->validateInputs($request);

        $gateCheck = $this->taskUpdaterService->checkGate($taskModel, $org, $project);

        if ($validation['code'] !== 200) {
            return response()->json([
                "code" => $validation['code'],
                "message" => $validation['message'],
                "task" => [],
            ]);
        }
        if ($gateCheck['code'] !== 200) {
            return response()->json([
                "code" => $gateCheck['code'],
                "message" => $gateCheck['message'],
                "task" => [],
            ]);
        }

        $this->taskUpdaterService->updateTask($task, $request, Auth::guard('api')->user());

        $this->taskUpdaterService->updateAssignees($task, $request);

        $this->taskUpdaterService->disconnectFiles($task, $request);

        $this->taskUpdaterService->updatePriorities($task, $request);

        $this->taskUpdaterService->updateStatuses($task, $request);

        return response()->json([
            "code" => 200,
            "message" => "Task sucessfully updated!",
            "task" => $task->load(['statuses', 'priorities', 'members.users', 'comments.users', 'files', 'edits.users']),
        ]);
    }

    public function uploadFile(Request $request, $orgUuid, $projectIuid, $taskUuid, Task $taskModel)
    {
        $org = Organization::where("org_uuid", $orgUuid)->first();
        $project = Project::where("project_uuid", $projectIuid)->first();
        $task = Task::where("task_uuid", $taskUuid)->first();

        if (!$org || !$project || !$task) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect task, project or organization entries.",
                "task" => [],
            ]);
        }

        $gateCheck = $this->taskFileHandlerService->checkGate($taskModel, $org, $project);

        if ($gateCheck['code'] !== 200) {
            return response()->json([
                "code" => $gateCheck['code'],
                "message" => $gateCheck['message'],
                "task" => [],
            ]);
        }

        $uploadFile = $this->taskFileHandlerService->uploadFile($request, $task, Auth::guard('api')->user());

        if ($uploadFile['code'] !== 200) {
            return response()->json([
                "code" => $uploadFile['code'],
                "message" => $uploadFile['message'],
                "task" => [],
            ]);
        }

        return response()->json([
            "code" => 200,
            "message" => "All attachments successfully uploaded.",
            "task" => $task->load(['statuses', 'priorities', 'members.users', 'comments.users', 'files']),
        ]);

        
    }

    public function assignees(Request $request, $taskUuid, $orgUUid, $projectUuid)
    {
        $task = Task::where("task_uuid", $taskUuid)->first();
        $project = $task->projects()->where('project_uuid', $projectUuid)->first();
        $organization = $project->organizations()->where('org_uuid', $orgUUid)->first();

        $members = [];
        $orgForeigns = [];
        $projectForeigns = [];
        $message = "";

        foreach (json_decode($request->assignees) as $assignee) {
            $user = User::where('email', $assignee)->first();

            if (!$task->members()->where('user_id', $user->id)->exists()) {
                if (!$organization->members()->where('user_id', $user->id)->exists()) {
                    $orgForeigns[] = $user->email;
                } else if (!$project->members()->where('user_id', $user->id)->exists()) {
                    $projectForeigns[] = $user->email;
                } else {
                    $members = Member::create([
                        "user_id" => $user->id,
                        "memberable_id" => $task->id,
                        "memberable_type" => "App\Models\Task",
                    ]);
                }
            }
        }

        if (!empty($projectForeigns) && !empty($orgForeigns)) {
            $message = rtrim(implode(', ', $orgForeigns), ',') . (count($orgForeigns) > 1 ? ' are' : ' is') . " not member of current organization, and " . rtrim(implode(',', $projectForeigns), ',') . (count($projectForeigns) > 1 ? ' are' : ' is') . " not member of current project.";
        } else if (!empty($projectForeigns)) {
            $message = rtrim(implode(', ', $projectForeigns), ',') . (count($projectForeigns) > 1 ? ' are' : ' is') . " not member of current project.";
        } else if (!empty($orgForeigns)) {
            $message = rtrim(implode(', ', $projectForeigns), ',') . (count($projectForeigns) > 1 ? ' are' : ' is') . " not member of current organization.";
        }

        return response()->json($message);
    }

    public function removeAssignee(Request $request, $uuid)
    {
        $task = Task::where("task_uuid", $uuid)->first();
        $user = User::where("email", $request->assignee)->first();

        if ($task->members()->where("user_id", $user->id)->first()->delete()) {
            return response()->json($task->members);
        }
        return response()->json([
            "error" => "Unable to remove assignee."
        ]);
    }

    public function destroyTask($orgUuid, $projectUUid, $uuid)
    {
        $task = Task::where("task_uuid", $uuid)->first();

        $task->priorities->pluck('id')->each(function ($priorityId) {
            $priority = Priority::find($priorityId);
            $priority->delete();
        });
        $task->statuses->pluck('id')->each(function ($statusId) {
            $status = Status::find($statusId);
            $status->delete();
        });

        $task->members()->delete();

        $this->taskUpdaterService->disconnectFiles($task, (object) ['files' => []]);

        if ($task->delete()) {
            return response()->json([
                "code" => 200,
                "message" => $task->title . ' deleted.',
            ]);
        }
        return response()->json([
            "code" => 500,
            "message" => "Unable to delete task. Please try again later."
        ]);
    }

    public function totalTasks($orgUuid, Organization $organization)
    {
        $org = $organization->where('org_uuid', $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => 'Incorrect organization entry!',
                "totalTasks" => 0,
            ]);
        }

        if (Gate::inspect('view', [$organization, $org->id])->denied()) {
            return response()->json([
                "code" => 403,
                "message" => 'You are not a member of the current organization!',
                "totalTasks" => 0,
            ]);
        }

        $totalTasks = $org->projects()->withCount('tasks as total_tasks')->get()
            ->pluck('total_tasks')->flatten()->sum();

        return response()->json([
            "code" => 200,
            "message" => '',
            "totalTasks" => $totalTasks,
        ]);
    }

    public function overdueTasks($orgUuid, Organization $organization)
    {
        $org = Organization::where('org_uuid', $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry!",
                "overdueTasks" => 0,
            ]);
        }

        if (Gate::inspect('view', [$organization, $org->id])->denied()) {
            return response()->json([
                "code" => 403,
                "message" => "You are not member of the current organization!",
                "overdueTasks" => 0,
            ]);
        }
        $overdueTasks = $org->projects()->withCount(['tasks as overdue_tasks_count' => function ($query) {
            $query->where('due_date', '<', now());
        }])->get()->pluck('overdue_tasks_count')->flatten()->sum();

        return response()->json([
            "code" => 200,
            "message" => "",
            "overdueTasks" => $overdueTasks,
        ]);
    }

    public function completedTasks($orgUuid, Organization $organization)
    {
        $org = Organization::where('org_uuid', $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry!",
                "completedTasks" => 0,
            ]);
        }

        if (Gate::inspect('view', [$organization, $org->id])->denied()) {
            return response()->json([
                "code" => 403,
                "message" => "You are not member of the current organization!",
                "completedTasks" => 0,
            ]);
        }
        $completedTasks = $org->projects()->withCount(['tasks as completed_tasks_count' => function ($query) {
            $query->where('is_completed', true);
        }])->get()->pluck('completed_tasks_count')->flatten()->sum();

        return response()->json([
            "code" => 200,
            "message" => "",
            "completedTasks" => $completedTasks,
        ]);
    }
}
