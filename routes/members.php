<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\MembersController;

//Signin Routes
Route::controller(MembersController::class)->group(function () {
    Route::get('/read/organization/{orgUuid}/members', 'getOrgMembers')
        ->middleware(['auth:sanctum', 'throttle:100,1']);
    Route::get('/read/project/{orgUuid}/{projectUuid}/members', 'getProjectMembers')
        ->middleware(['auth:sanctum', 'throttle:100,1']);
    Route::get('/read/task/{orgUuid}/{projectUuid}/{taskUuid}/members', 'getTaskMembers')
        ->middleware(['auth:sanctum', 'throttle:100,1']);

    Route::delete('/remove/organization/{orgId}/member/{memberId}', 'deleteOrgMember')
        ->middleware(['auth:sanctum', 'throttle:100,1']);
});