<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\OrganizationController;

//Signin Routes
Route::controller(OrganizationController::class)->group(function () {
    Route::post('/organization/create', 'createOrganization')
        ->middleware(['auth:sanctum']);

    Route::get('/first/organization', 'getFirstOrg')
        ->middleware(['auth:sanctum']);

    Route::get('/organization/read/{orgUuid}', 'getOrganization')
        ->middleware(['auth:sanctum']);

    Route::get('/organizations/read', 'getOrganizations')
        ->middleware(['auth:sanctum']);

    Route::post('/organization/update/{orgUuid}', 'updateOrganization')
        ->middleware(['auth:sanctum']);

    Route::delete('/organization/delete/{orgUuid}', 'deleteOrganization')
        ->middleware(['auth:sanctum']);

    Route::post('/organization/invitation', 'generateInvitationLink')
        ->middleware(['auth:sanctum']);

    Route::get('/organization/new/user/invitation/{email}/{signature}/check', 'newUserInvitationCheck')
        ->middleware(['guest']);

    Route::post('/organization/new/user/invitation/acceptance/{email}/{signature}', 'newUserInviteAcceptance')
        ->middleware(['guest']);
});