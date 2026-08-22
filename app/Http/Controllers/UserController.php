<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Provide seeded users for testers so they can choose an id manually.
     */
    public function index(): JsonResponse
    {
        // Counts make it clear which demo users already have reward progress.
        $users = User::query()
            ->withCount(['achievements', 'badges', 'purchases', 'cashbackTransactions'])
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'achievements_count' => $user->achievements_count,
                'badges_count' => $user->badges_count,
                'purchases_count' => $user->purchases_count,
                'cashback_transactions_count' => $user->cashback_transactions_count,
            ]);

        return response()->json([
            'data' => $users,
        ]);
    }
}
