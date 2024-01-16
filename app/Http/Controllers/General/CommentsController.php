<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Models\Organization;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CommentsController extends Controller
{
    public function create(Request $request, $orgUuid, $projectUuid, $taskUuid)
    {
        $validator = Validator::make($request->all(), [
            "comment" => ["required", "min:1", "max:5000"],
        ]
        );

        if ($validator->fails()) {
            return response()->json([
                "code" => 422,
                "newComment" => '',
                "message" => $validator->errors(),
            ]);
        }

        $user = Auth::guard('api')->user();
        $member = $user->members()->where('memberable_type', 'App\Models\Organization')->pluck('memberable_id');
        $org = Organization::whereIn('id', $member)->where('org_uuid', $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry!",
                "newComment" => '',
            ]);
        }

        $project = $org->projects()->where('project_uuid', $projectUuid)->first();
        if (!$project) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect project entry!",
                "newComment" => '',
            ]);
        }

        $task = $project->tasks()->where('task_uuid', $taskUuid)->first();
        if (!$task) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect task entry!",
                "newComment" => '',
            ]);
        }

        $comment = Comment::create([
            'comment_uuid' => Str::uuid(),
            'user_id' => $user->id,
            'commentable_type' => 'App\Models\Task',
            'commentable_id' => $task->id,
            'comment' => $request->comment,
        ]);

        return response()->json([
            "code" => 200,
            "message" => "Comment deleted",
            'newComment' => $comment->load('users'),
        ]);
    }

    public function deleteComment($orgUuid, $projectUuid, $taskUuid, $commentUuid)
    {
        $user = Auth::guard('api')->user();
        $member = $user->members()->where('memberable_type', 'App\Models\Organization')->pluck('memberable_id');
        $org = Organization::whereIn('id', $member)->where('org_uuid', $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry!"
            ]);
        }

        $project = $org->projects()->where('project_uuid', $projectUuid)->first();
        if (!$project) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect project entry!"
            ]);
        }

        $task = $project->tasks()->where('task_uuid', $taskUuid)->first();
        if (!$task) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect task entry!"
            ]);
        }

        $comment = $task->comments()->where('comment_uuid', $commentUuid)->where('user_id', $user->id)->first();
        if (!$comment) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect comment entry!"
            ]);
        }
        $comment->delete();

        return response()->json([
            "code" => 200,
            "message" => "Comment deleted"
        ]);
    }

    public function saveComment(Request $request, $orgUuid, $projectUuid, $taskUuid, $commentUuid)
    {
        $user = Auth::guard('api')->user();
        $member = $user->members()->where('memberable_type', 'App\Models\Organization')->pluck('memberable_id');
        $org = Organization::whereIn('id', $member)->where('org_uuid', $orgUuid)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect organization entry!"
            ]);
        }

        $project = $org->projects()->where('project_uuid', $projectUuid)->first();
        if (!$project) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect project entry!"
            ]);
        }

        $task = $project->tasks()->where('task_uuid', $taskUuid)->first();
        if (!$task) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect task entry!"
            ]);
        }

        $comment = $task->comments()->where('comment_uuid', $commentUuid)->where('user_id', $user->id)->first();
        if (!$comment) {
            return response()->json([
                "code" => 404,
                "message" => "Incorrect comment entry!"
            ]);
        }
        $comment->comment = $request->comment;
        $comment->save();

        return response()->json([
            "code" => 200,
            "message" => "Comment deleted"
        ]);
    }
}
