<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\ProjectController;

//Signin Routes
Route::controller(ProjectController::class)->group(function () {
    Route::get('/project/read/projects/{orgUuid}', 'getProjects')
        ->middleware(['auth:sanctum']);

    Route::get('/project/active/project/{orgUuid}/{projectUuid}', 'currentProject')
        ->middleware(['auth:sanctum']);

    Route::post('/project/create/{orgUuid}', 'createProject')
        ->middleware(['auth:sanctum']);

    Route::post('/project/update/{orgUuid}/{projectUuid}', 'updateProject')
        ->middleware(['auth:sanctum']);

    Route::delete('/project/delete/{orgUuid}/{projectUuid}', 'deleteProject')
        ->middleware(['auth:sanctum']);

    Route::post('/project/invite/members/{projectUuid}/{orgUuid}', 'addMembers')
        ->middleware(['auth:sanctum']);

    Route::get('/project/total/count/{orgUuid}', 'totalProjects')
        ->middleware(['auth:sanctum']);

    Route::get('/project/show/{orgUuid}/{projectUuid}', 'showProject')
        ->middleware(['auth:sanctum']);
});