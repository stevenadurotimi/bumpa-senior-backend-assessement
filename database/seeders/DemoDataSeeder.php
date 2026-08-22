<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\CashbackTransaction;
use App\Models\Purchase;
use App\Models\User;
use App\Models\UserPayoutAccount;
use App\Support\RewardDefinitions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed realistic demo data for manual API testing.
     */
    public function run(): void
    {
        // Load definition records once so the seeded users can represent
        // realistic progress states.
        $firstPurchase = Achievement::query()->where('name', RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE)->sole();
        $fivePurchases = Achievement::query()->where('name', RewardDefinitions::ACHIEVEMENT_5_PURCHASES)->sole();
        $tenPurchases = Achievement::query()->where('name', RewardDefinitions::ACHIEVEMENT_10_PURCHASES)->sole();

        $beginner = Badge::query()->where('name', RewardDefinitions::BADGE_BEGINNER)->sole();
        $intermediate = Badge::query()->where('name', RewardDefinitions::BADGE_INTERMEDIATE)->sole();

        // No progress yet: useful for the empty achievement endpoint response.
        $newUser = $this->user('New Customer', 'new.customer@example.com');
        $this->payoutAccount($newUser, 'New Customer');

        // First-purchase state: one achievement, one badge, one cashback.
        $beginnerUser = $this->user('Beginner Customer', 'beginner.customer@example.com');
        $beginnerPayoutAccount = $this->payoutAccount($beginnerUser, 'Beginner Customer');
        $this->purchases($beginnerUser, 1);
        $this->unlockAchievements($beginnerUser, [$firstPurchase]);
        $this->unlockBadges($beginnerUser, [$beginner]);
        $this->successfulCashback($beginnerUser, $beginnerPayoutAccount, $beginner);

        // Active state: enough purchases to show the next purchase milestone.
        $activeUser = $this->user('Active Customer', 'active.customer@example.com');
        $activePayoutAccount = $this->payoutAccount($activeUser, 'Active Customer');
        $this->purchases($activeUser, 5);
        $this->unlockAchievements($activeUser, [$firstPurchase, $fivePurchases]);
        $this->unlockBadges($activeUser, [$beginner]);
        $this->successfulCashback($activeUser, $activePayoutAccount, $beginner);

        // Higher purchase count without the next badge yet.
        $powerUser = $this->user('Power Customer', 'power.customer@example.com');
        $powerPayoutAccount = $this->payoutAccount($powerUser, 'Power Customer');
        $this->purchases($powerUser, 10);
        $this->unlockAchievements($powerUser, [$firstPurchase, $fivePurchases, $tenPurchases]);
        $this->unlockBadges($powerUser, [$beginner]);
        $this->successfulCashback($powerUser, $powerPayoutAccount, $beginner);

        // Failure state: badge unlocked but cashback cannot be paid.
        $missingPayoutUser = $this->user('Missing Payout Customer', 'missing.payout@example.com');
        $this->purchases($missingPayoutUser, 1);
        $this->unlockAchievements($missingPayoutUser, [$firstPurchase]);
        $this->unlockBadges($missingPayoutUser, [$beginner]);
        $this->failedCashback($missingPayoutUser, $beginner, 'User does not have a payout account.');

        // Multi-badge state: useful for checking current/next badge behavior.
        $intermediateUser = $this->user('Intermediate Customer', 'intermediate.customer@example.com');
        $intermediatePayoutAccount = $this->payoutAccount($intermediateUser, 'Intermediate Customer');
        $this->purchases($intermediateUser, 20);
        $this->unlockAchievements($intermediateUser, Achievement::query()->orderBy('threshold')->get()->all());
        $this->unlockBadges($intermediateUser, [$beginner, $intermediate]);
        $this->successfulCashback($intermediateUser, $intermediatePayoutAccount, $beginner);
        $this->successfulCashback($intermediateUser, $intermediatePayoutAccount, $intermediate);
    }

    private function user(string $name, string $email): User
    {
        // Every demo user uses the same password for simple manual login if auth
        // is added later.
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }

    private function payoutAccount(User $user, string $accountName): UserPayoutAccount
    {
        // Use deterministic account numbers so reseeding updates, not duplicates.
        return UserPayoutAccount::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'provider' => 'payment_mock',
                'bank_code' => '044',
                'account_number' => str_pad((string) $user->getKey(), 10, '0', STR_PAD_LEFT),
                'account_name' => $accountName,
                'currency' => 'NGN',
            ],
        );
    }

    private function purchases(User $user, int $count): void
    {
        foreach (range(1, $count) as $purchaseNumber) {
            // References are deterministic to keep the seeder idempotent.
            Purchase::query()->updateOrCreate(
                ['reference' => "seed-user-{$user->getKey()}-purchase-{$purchaseNumber}"],
                [
                    'user_id' => $user->getKey(),
                    'amount' => 5000 + ($purchaseNumber * 100),
                ],
            );
        }
    }

    /**
     * @param  array<int, Achievement>  $achievements
     */
    private function unlockAchievements(User $user, array $achievements): void
    {
        foreach ($achievements as $achievement) {
            $user->achievements()->syncWithoutDetaching([
                $achievement->getKey() => ['unlocked_at' => now()],
            ]);
        }
    }

    /**
     * @param  array<int, Badge>  $badges
     */
    private function unlockBadges(User $user, array $badges): void
    {
        foreach ($badges as $badge) {
            $user->badges()->syncWithoutDetaching([
                $badge->getKey() => ['unlocked_at' => now()],
            ]);
        }
    }

    private function successfulCashback(User $user, UserPayoutAccount $payoutAccount, Badge $badge): void
    {
        // Seed the same shape the real listener writes after a successful payout.
        CashbackTransaction::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'badge_id' => $badge->getKey(),
            ],
            [
                'user_payout_account_id' => $payoutAccount->getKey(),
                'amount_kobo' => 30000,
                'provider' => 'payment_mock',
                'idempotency_key' => "badge-cashback:{$user->getKey()}:{$badge->getKey()}",
                'status' => 'successful',
                'provider_reference' => "mock-transfer-cashback-{$user->getKey()}-{$badge->getKey()}",
                'failure_reason' => null,
                'metadata' => [
                    'provider_status' => 'successful',
                    'seeded' => true,
                ],
            ],
        );
    }

    private function failedCashback(User $user, Badge $badge, string $failureReason): void
    {
        // Seed a failure case so testers can inspect error-state cashback rows.
        CashbackTransaction::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'badge_id' => $badge->getKey(),
            ],
            [
                'user_payout_account_id' => null,
                'amount_kobo' => 30000,
                'provider' => 'payment_mock',
                'idempotency_key' => "badge-cashback:{$user->getKey()}:{$badge->getKey()}",
                'status' => 'failed',
                'provider_reference' => null,
                'failure_reason' => $failureReason,
                'metadata' => [
                    'provider_status' => 'failed',
                    'seeded' => true,
                ],
            ],
        );
    }
}
