<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\SearchController;



Route::controller(SearchController::class)->group(function () {
    Route::get('/search/{orgId}', 'search')
    ->where('orgId', '[a-z0-9\-]+')->middleware(['auth:sanctum']);
});

//Authentication Routes
require __DIR__ . '/auth.php';
require __DIR__ . '/tasks.php';
require __DIR__ . '/projects.php';
require __DIR__ . '/organization.php';
require __DIR__ . '/priorities.php';
require __DIR__ . '/statuses.php';
require __DIR__ . '/invitations.php';
require __DIR__ . '/members.php';
require __DIR__ . '/comments.php';
require __DIR__ . '/profile.php';