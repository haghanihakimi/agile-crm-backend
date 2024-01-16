<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Priority;
use Illuminate\Support\Str;

class PrioritiesController extends Controller
{
    public function showPriorities($taskUuid): object
    {
        $task = Task::where("task_uuid", $taskUuid)->first();

        $priorities = $task->priorities;

        return response()->json($priorities);
    }

    public function updatePriority(Request $request, $taskUuid): object
    {
        /**
         * Sample - form-data:
         * key: priorities
         * value: [ { "id": 29, "name": "Normal"}, { "id": 30, "name": "High" }, { "id": 31, "name": "ASAP" }, { "name": "Urgent" } ]
         */
        $task = Task::where("task_uuid", $taskUuid)->first();

        foreach (json_decode($request->priorities) as $priority) {
            if (isset($priority->id)) {
                $editablePriority = $task->priorities()->wherePivot('id', $priority->id)->first();
                $editablePriority->name = $priority->name;
                $editablePriority->save();
            } else {
                $priority = Priority::create(["name" => $priority->name]);
                $priority->tasks()->attach($task->id);
            }
        }

        return response()->json($task->priorities);
    }

    public function deletePriority(Request $request, $taskUuid): object
    {
        /**
         * Sample - form-data:
         * key: priorities
         * value: [priorities_id]
         */
        $task = Task::where("task_uuid", $taskUuid)->first();

        $deletion = $task->priorities()->withPivot('priority_id')->whereIn('priority_id', json_decode($request->priorities))->delete();

        if ($deletion) {
            return response()->json([
                'message' => 'Selected tasks removed.'
            ]);
        } else {
            return response()->json([
                'message' => 'Unable to remove selcted tags. Please try again later.'
            ]);
        }
    }
}
