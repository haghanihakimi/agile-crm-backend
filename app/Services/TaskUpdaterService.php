<?php

namespace App\Services;

use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\EditsRequest;

class TaskUpdaterService
{
    protected $editsRequest;

    public function __construct(EditsRequest $editsRequest)
    {
        $this->editsRequest = $editsRequest;
    }

    public function validateInputs($request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "title" => ["required", "string", "max:255", "min:5"],
                "task_assignees" => ["array", "nullable"],
                "description" => ["nullable", "string", "max:5000"],
                "priority" => ["array", "required", "min:1"],
                "status" => ["array", "required", "min:1"],
                "private" => ["required", "boolean"],
                "due_date" => ["required", "string"],
            ]
        );

        if ($validator->fails()) {
            return [
                "code" => 422,
                "message" => $validator->errors(),
            ];
        }
        return [
            "code" => 200,
            "message" => "",
        ];
    }
    public function checkGate($taskModel, $org, $project)
    {
        $taskGuard = Gate::inspect('update', [$taskModel, $org->id, $project->id]);
        if ($taskGuard->denied()) {
            return [
                "code" => 403,
                "message" => "You are not a member of current project or organization!",
            ];
        }
        return [
            "code" => 200,
            "message" => "",
        ];
    }
    public function updateTask($task, $request, $user)
    {
        $task->update([
            "title" => $request->title,
            "description" => $request->description,
            "due_date" => Carbon::parse($request->due_date)->format("Y-m-d H:i:s"),
            "private" => $request->private,
        ]);

        $this->editsRequest->store(["edited_by" => $user->id, "type" => 'App\Models\Task', "id" => $task->id]);
    }
    public function updateAssignees($task, $request)
    {
        $assignees = $request->task_assignees;

        $task->members()->when(empty($assignees), function ($query) {
            // This condition is true when $assignees is empty
            $query->delete();
        }, function ($query) use ($assignees, $task) {
            // This condition is true when $assignees is not empty
            // Remove members whose emails are not in $assignees
            $query->whereHas('users', function ($subQuery) use ($assignees) {
                $subQuery->whereNotIn('email', $assignees);
            })->delete();

            // Add new members for user_ids in $assignees that are not in $task->members()
            collect($assignees)->each(function ($userId) use ($query, $task) {
                $user = User::where('email', $userId)->first();
                if ($user) {
                    // Check if the user is not already a member of the task
                    if (!$task->members()->where('user_id', $user->id)->exists()) {
                        $query->create([
                            'user_id' => $user->id,
                            'memberable_type' => 'App\Models\Task',
                            'memberable_id' => $task->id,
                        ]);
                    }
                }
            });
        });
    }

    public function disconnectFiles($task, $request)
    {
        $files = $request->files;

        if (count($task->files) > 0) {
            $task->files()->when(empty($files), function ($query) {
                $filesToDelete = $query->get();
                $query->delete();

                foreach ($filesToDelete as $file) {
                    Storage::delete('public/' . $file->file_path);
                }
            }, function ($query) use ($files) {
                $fileIds = collect($files)->pluck('file_uuid')->toArray();

                $filesToDelete = $query->whereNotIn('file_uuid', $fileIds)->get();

                $query->whereNotIn('file_uuid', $fileIds)->delete();

                foreach ($filesToDelete as $file) {
                    Storage::delete('public/' . $file->file_path);
                }
            });
        }
    }

    public function updatePriorities($task, $request)
    {
        $priorityIds = $request->priority
            ? collect($request->priority)->pluck('id')->filter()
            : collect();
        $prioritiesWithoutId = collect($request->priority)->where('id', null)->values();
        $existingPriorities = collect($request->priority)->where('id', '!=', null)->values();

        // Check if there is any existing priorities in $request->priority and if anything in them is changed
        if (count($existingPriorities) > 0) {
            foreach ($existingPriorities as $priority) {
                $existingPriority = Priority::find($priority['id']);
                $existingPriority->name = $priority['title'];
                $existingPriority->is_selected = $priority['is_selected'];
                $existingPriority->save();
            }
        }


        // Delete the existing priority if it doesn't exist in $request array
        $deletePriority = $task->priorities->whereNotIn('pivot.priority_id', $priorityIds)->each(function ($priority) {
            $priority->delete();
        });

        // // Check if there is any new item in the $request which doesn't exist in DB.
        if (count($prioritiesWithoutId) > 0) {
            foreach ($prioritiesWithoutId as $priority) {
                $newPriority = Priority::create([
                    'name' => $priority['title'],
                    'is_selected' => $priority['is_selected'],
                ]);
                if ($priority['is_selected']) {
                    $task->priorities->where('pivot.priority_id', '!=', $newPriority->id)->each(function ($priority) {
                        $priority->is_selected = false;
                        $priority->save();
                    });
                }
                $newPriority->tasks()->attach($task->id);
            }
        }
    }

    public function updateStatuses($task, $request)
    {
        $statusIds = $request->status
            ? collect($request->status)->pluck('id')->filter()
            : collect();
        $statusesWithoutId = collect($request->status)->where('id', null)->values();
        $existingStatuses = collect($request->status)->where('id', '!=', null)->values();

        // Check if there is any existing statuses in $request->status and if anything in them is changed
        if (count($existingStatuses) > 0) {
            foreach ($existingStatuses as $status) {
                $existingStatus = Status::find($status['id']);
                $existingStatus->name = $status['title'];
                $existingStatus->is_selected = $status['is_selected'];
                $existingStatus->save();
            }
        }


        // Delete the existing status if it doesn't exist in $request array
        $deleteStatus = $task->statuses->whereNotIn('pivot.status_id', $statusIds)->each(function ($status) {
            $status->delete();
        });

        // Check if there is any new item in the $request which doesn't exist in DB.
        if (count($statusesWithoutId) > 0) {
            foreach ($statusesWithoutId as $status) {
                $newStatus = Status::create([
                    'name' => $status['title'],
                    'is_selected' => $status['is_selected'],
                ]);
                if ($status['is_selected']) {
                    $task->statuses->where('pivot.status_id', '!=', $newStatus->id)->each(function ($status) {
                        $status->is_selected = false;
                        $status->save();
                    });
                }
                $newStatus->tasks()->attach($task->id);
            }
        }
    }
}