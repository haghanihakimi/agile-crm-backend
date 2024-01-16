<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\CommentsController;

//Signin Routes
Route::controller(CommentsController::class)->group(function () {
    Route::post('/comment/task/post/comment/{orgUuid}/{projectUuid}/{taskUuid}', 'create')
        ->middleware(['auth:sanctum']);

    Route::post('/comment/task/save/comment/{orgUuid}/{projectUuid}/{taskUuid}/{commentUuid}', 'saveComment')
        ->middleware(['auth:sanctum']);

    Route::delete('/comment/task/delete/{orgUuid}/{projectUuid}/{taskUuid}/{commentUuid}', 'deleteComment')
        ->middleware(['auth:sanctum']);
});