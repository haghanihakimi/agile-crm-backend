<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\MembersController;

//Signin Routes
Route::controller(MembersController::class)->group(function () {
    Route::post('/members/invitations', 'inviteMembers')
        ->middleware(['auth:sanctum']);
});