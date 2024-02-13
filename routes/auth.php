<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\RestoreAccountController;

//Signin Routes
Route::controller(LoginController::class)->group(function () {
    Route::post('/signin', 'login')->middleware(['guest']);

    Route::get('/auth/check', function () {
        return response()->json(Auth::guard('api')->check() ? [
            "user" => Auth::guard('api')->user()->makeVisible(['phone', 'email_verified_at', 'created_at']),
            "orgs" => Auth::guard('api')->user()->members()->where('memberable_type', 'App\Models\Organization')->count(),
            "projects" => Auth::guard('api')->user()->members()->where('memberable_type', 'App\Models\Project')->count(),
            "tasks" => Auth::guard('api')->user()->members()->where('memberable_type', 'App\Models\Task')->count(),
        ] : false);
    });
    
    Route::post('/signout', 'signout')->middleware(['auth:sanctum']);
});

// Registration Routes
Route::controller(RegistrationController::class)->group(function () {
    Route::post('/user/create/account', 'store')
        ->middleware(['guest']);
});

// Restore account Routes
Route::controller(RestoreAccountController::class)->group(function () {
    Route::post('/forgot-password', 'sendLink')
        ->middleware(['guest']);

    Route::get('/reset-password/{token}', 'resetPassword')
        ->middleware('guest')->name('password.reset');
});