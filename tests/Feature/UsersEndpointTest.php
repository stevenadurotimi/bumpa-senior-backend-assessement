<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a list of users for manual endpoint testing', function () {
    $firstUser = User::factory()->create([
        'name' => 'First User',
        'email' => 'first@example.com',
    ]);

    $secondUser = User::factory()->create([
        'name' => 'Second User',
        'email' => 'second@example.com',
    ]);

    $secondUser->purchases()->create([
        'reference' => 'purchase-001',
        'amount' => 5000,
    ]);

    $this->getJson('/users')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                [
                    'id' => $firstUser->id,
                    'name' => 'First User',
                    'email' => 'first@example.com',
                    'achievements_count' => 0,
                    'badges_count' => 0,
                    'purchases_count' => 0,
                    'cashback_transactions_count' => 0,
                ],
                [
                    'id' => $secondUser->id,
                    'name' => 'Second User',
                    'email' => 'second@example.com',
                    'achievements_count' => 0,
                    'badges_count' => 0,
                    'purchases_count' => 1,
                    'cashback_transactions_count' => 0,
                ],
            ],
        ]);
});
