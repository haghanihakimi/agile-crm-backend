<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\MembersController;

//Signin Routes
Route::controller(MembersController::class)->group(function () {
    Route::get('/members/fetch/invitations', 'invitations')
        ->middleware(['auth:sanctum']);

    Route::get('/invitation/validation/{email}/{signature}', 'validateInvitation')
        ->middleware(['guest']);

    Route::post('/organization/invitation', 'generateInvitationLink')
        ->middleware(['auth:sanctum']);

    Route::post('/members/invitations', 'inviteMembers')
        ->middleware(['auth:sanctum']);

    Route::post('/invitation/acceptance/{invitationId}', 'acceptInvitation')
        ->middleware(['auth:sanctum']);

    Route::delete('/invitation/reject/{invitationId}', 'rejectInvitation')
        ->middleware(['auth:sanctum']);
});