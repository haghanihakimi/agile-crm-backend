<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TaskFileHandlerService
{
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

    public function uploadFile($request, $task, $user)
    {
        $failedUploads = [];

        if ($request->hasFile('newFiles')) {
            foreach ($request->file('newFiles') as $file) {
                $originalName = $file->getClientOriginalName();
                $originalMimeType = $file->getClientMimeType();
                $originalFormat = $file->getClientOriginalExtension();
                $originalFileSize = $file->getSize();
                $hashName = $file->hashName();

                $stored = $file->storeAs('uploads/tasks/' . $task->task_uuid, $hashName, 'public');

                if ($stored) {
                    $uploadedFile = File::create([
                        'file_uuid' => Str::uuid(),
                        'uploaded_by' => $user->id,
                        'fileable_type' => 'App\Models\Task',
                        'fileable_id' => $task->id,
                        'file_name' => $hashName,
                        'original_name' => $originalName,
                        'file_format' => $originalFormat,
                        'file_type' => $originalMimeType,
                        'file_path' => $stored,
                        'file_size' => $originalFileSize,
                        'used_for' => 'Tasks',
                    ]);
                } else {
                    $failedUploads = [
                        "code" => 500,
                        "message" => "Some files cannot be uploaded.",
                        "task" => [],
                    ];
                }
            }
            if (empty($failedUploads)) {
                return [
                    "code" => 200,
                    "message" => "All attachments successfully uploaded.",
                    "task" => $task->load(['statuses', 'priorities', 'members.users', 'comments.users', 'files', 'edits.users']),
                ];
            }
            return $failedUploads;
        }
        return [
            "code" => 422,
            "message" => "Please select files to upload!",
            "task" => $task->load(['statuses', 'priorities', 'members.users', 'comments.users', 'files', 'edits.users']),
        ];
    }
}