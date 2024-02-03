<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\General\UserController;

//Signin Routes
Route::controller(UserController::class)->group(function () {
    Route::patch('/save/profile/settings', 'saveSettings')->middleware(['auth:sanctum']);

    Route::post('/change/profile/picture', 'changePicture')->middleware(['auth:sanctum']);

    Route::delete('/delete/user/account', 'delete')->middleware(['auth:sanctum']);
});