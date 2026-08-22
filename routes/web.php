<?php

use App\Http\Controllers\UserAchievementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Health-style root route for quick API-only smoke checks.
Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
    ]);
});

// Demo helper endpoint: testers can fetch users and choose an id.
Route::get('/users', [UserController::class, 'index']);

// Assessment endpoint: returns the user's reward progress.
Route::get('/users/{user}/achievements', UserAchievementController::class);
