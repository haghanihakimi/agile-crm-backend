<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\PrioritiesController;

//Signin Routes
Route::controller(PrioritiesController::class)->group(function () {
    Route::get('/priority/show/{taskUuid}', 'showPriorities')
        ->middleware(['auth:sanctum', 'throttle:100,1']);

    Route::post('/priority/update/{taskUuid}', 'updatePriority')
        ->middleware(['auth:sanctum', 'throttle:100,1']);

    Route::post('/priority/delete/{taskUuid}', 'deletePriority')
        ->middleware(['auth:sanctum', 'throttle:100,1']);
});