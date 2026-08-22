<?php

use App\Http\Controllers\UserAchievementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
    ]);
});

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}/achievements', UserAchievementController::class);
