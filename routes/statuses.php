<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\StatusesController;

//Signin Routes
Route::controller(StatusesController::class)->group(function () {
    Route::get('/status/show/{taskUuid}', 'showStatuses')
        ->middleware(['auth:sanctum', 'throttle:100,1']);

    Route::post('/status/update/{taskUuid}', 'updateStatus')
        ->middleware(['auth:sanctum', 'throttle:100,1']);

    Route::post('/status/delete/{taskUuid}', 'deleteStatus')
        ->middleware(['auth:sanctum', 'throttle:100,1']);
});