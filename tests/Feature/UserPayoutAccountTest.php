<?php

use App\Models\Badge;
use App\Models\User;
use App\Models\UserPayoutAccount;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores one payout account for a user', function () {
    $user = User::factory()->create();

    $payoutAccount = $user->payoutAccount()->create([
        'provider' => 'flutterwave',
        'bank_code' => '044',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
        'currency' => 'NGN',
    ]);

    expect($user->refresh()->payoutAccount)
        ->toBeInstanceOf(UserPayoutAccount::class)
        ->and($user->payoutAccount->is($payoutAccount))->toBeTrue()
        ->and($payoutAccount->user->is($user))->toBeTrue();
});

it('has many cashback transactions', function () {
    $user = User::factory()->create();
    $badge = Badge::query()->create([
        'name' => 'Beginner',
        'required_achievements_count' => 1,
        'sort_order' => 1,
    ]);

    $payoutAccount = $user->payoutAccount()->create([
        'provider' => 'flutterwave',
        'bank_code' => '044',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
        'currency' => 'NGN',
    ]);

    $transaction = $payoutAccount->cashbackTransactions()->create([
        'user_id' => $user->id,
        'badge_id' => $badge->id,
        'amount_kobo' => 30000,
        'provider' => 'flutterwave',
        'idempotency_key' => "badge-cashback:{$user->id}:{$badge->id}",
        'status' => 'successful',
    ]);

    expect($payoutAccount->cashbackTransactions()->sole()->is($transaction))->toBeTrue()
        ->and($transaction->payoutAccount->is($payoutAccount))->toBeTrue();
});

it('allows only one payout account per user', function () {
    $user = User::factory()->create();

    UserPayoutAccount::query()->create([
        'user_id' => $user->id,
        'provider' => 'flutterwave',
        'bank_code' => '044',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
        'currency' => 'NGN',
    ]);

    UserPayoutAccount::query()->create([
        'user_id' => $user->id,
        'provider' => 'flutterwave',
        'bank_code' => '058',
        'account_number' => '9876543210',
        'account_name' => 'Jane Doe',
        'currency' => 'NGN',
    ]);
})->throws(UniqueConstraintViolationException::class);
