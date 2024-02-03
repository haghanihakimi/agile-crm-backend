<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\OrganizationController;

//Signin Routes
Route::controller(OrganizationController::class)->group(function () {
    Route::post('/organization/create', 'createOrganization')
        ->middleware(['auth:sanctum']);

    Route::get('/active/organization', 'getActiveOrg')
        ->middleware(['auth:sanctum']);

    Route::get('/organization/read/{orgUuid}', 'getOrganization')
        ->middleware(['auth:sanctum']);

    Route::get('/organizations/read', 'getOrganizations')
        ->middleware(['auth:sanctum']);

    Route::post('/organization/update/{orgUuid}', 'updateOrganization')
        ->middleware(['auth:sanctum']);

    Route::delete('/organization/delete/{orgUuid}', 'deleteOrganization')
        ->middleware(['auth:sanctum']);
});