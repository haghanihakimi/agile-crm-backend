<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Status;

class StatusesController extends Controller
{
    public function showStatuses($taskUuid): object
    {
        $task = Task::where("task_uuid", $taskUuid)->first();

        $statuses = $task->statuses;

        return response()->json($statuses);
    }

    public function updateStatus(Request $request, $taskUuid): object
    {
        /**
         * Sample - form-data:
         * key: statuses
         * value: [ { "id": 22, "name": "Began"}, { "id": 23, "name": "Doing" }, { "id": 24, "name": "Todo" }, { "name": "Done" } ]
         */
        $task = Task::where("task_uuid", $taskUuid)->first();

        foreach (json_decode($request->statuses) as $status) {
            if (isset($status->id)) {
                $editableStatus = $task->statuses()->wherePivot('id', $status->id)->first();
                $editableStatus->name = $status->name;
                $editableStatus->save();
            } else {
                $status = Status::create(["name" => $status->name]);
                $status->tasks()->attach($task->id);
            }
        }

        return response()->json($task->statuses);
    }

    public function deleteStatus(Request $request, $taskUuid): object
    {
        /**
         * Sample - form-data:
         * key: statuses
         * value: [statuses_id]
         */
        $task = Task::where("task_uuid", $taskUuid)->first();

        $deletion = $task->statuses()->withPivot('status_id')->whereIn('status_id', json_decode($request->statuses))->delete();

        if ($deletion) {
            return response()->json([
                'message' => 'Selected status removed.'
            ]);
        } else {
            return response()->json([
                'message' => 'Unable to remove selcted status. Please try again later.'
            ]);
        }
    }
}
