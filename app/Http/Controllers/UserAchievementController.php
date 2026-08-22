<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;

class UserAchievementController extends Controller
{
    /**
     * Return the exact achievement-progress shape requested by the assessment.
     */
    public function __invoke(User $user, AchievementService $achievementService): JsonResponse
    {
        return response()->json($achievementService->progressFor($user));
    }
}
