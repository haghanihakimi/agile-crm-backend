<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\TaskController;

//Signin Routes
Route::controller(TaskController::class)->group(function () {
    Route::post('/task/create/{orgUuid}/{projectUuid}', 'create')
        ->middleware(['auth:sanctum']);

    Route::get('/tasks/read/tasks/{orgUuid}/{projectUuid}', 'getTasks')
        ->middleware(['auth:sanctum']);

    Route::get('/tasks/read/{projectUuid}', 'getTask')
        ->middleware(['auth:sanctum']);

    Route::patch('/task/update/{orgUuid}/{projectIuid}/{taskUuid}', 'updateTask')
        ->middleware(['auth:sanctum']);

    Route::post('/task/upload/files/{orgUuid}/{projectIuid}/{taskUuid}', 'uploadFile')
        ->middleware(['auth:sanctum']);

    Route::post('/task/upload/file/{uuid}', 'uploadFile')
        ->middleware(['auth:sanctum']);

    Route::post('/task/disconnect/file/{uuid}', 'disconnectFile')
        ->middleware(['auth:sanctum']);

    Route::post('/task/{taskUuid}/{orgUUid}/{projectUuid}/assign/members', 'assignees')
        ->middleware(['auth:sanctum']);

    Route::post('/task/{uuid}/remove/members', 'removeAssignee')
        ->middleware(['auth:sanctum']);

    Route::delete('/task/delete/{orgUuid}/{projectUuid}/{uuid}', 'destroyTask')
        ->middleware(['auth:sanctum']);

    Route::get('/tasks/count/tasks/{orgUuid}', 'totalTasks')
        ->middleware(['auth:sanctum']);

    Route::get('/overdue/tasks/{orgUuid}', 'overdueTasks')
        ->middleware(['auth:sanctum']);

    Route::get('/completed/tasks/{orgUuid}', 'completedTasks')
        ->middleware(['auth:sanctum']);
});