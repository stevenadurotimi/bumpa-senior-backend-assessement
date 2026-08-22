<?php

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\CashbackTransaction;
use App\Models\User;
use App\Payments\CashbackPaymentRequest;
use App\Payments\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

class FakeCashbackPaymentProvider implements CashbackPaymentProvider
{
    /** @var list<CashbackPaymentRequest> */
    public array $requests = [];

    public function __construct(private PaymentResult $result) {}

    public function sendCashback(CashbackPaymentRequest $request): PaymentResult
    {
        $this->requests[] = $request;

        return $this->result;
    }
}

function createCashbackUserWithPayoutAccount(): User
{
    $user = User::factory()->create();

    $user->payoutAccount()->create([
        'provider' => 'flutterwave',
        'bank_code' => '044',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
        'currency' => 'NGN',
    ]);

    return $user;
}

function createCashbackBadge(string $name = 'Beginner'): Badge
{
    return Badge::query()->create([
        'name' => $name,
        'required_achievements_count' => 1,
        'sort_order' => 1,
    ]);
}

it('awards 300 naira cashback when a badge is unlocked', function () {
    config(['cashback.provider' => 'flutterwave']);

    $user = createCashbackUserWithPayoutAccount();
    $badge = createCashbackBadge();
    $provider = new FakeCashbackPaymentProvider(PaymentResult::success('NEW', 'trf_123', [
        'id' => 'trf_123',
    ]));

    app()->instance(CashbackPaymentProvider::class, $provider);

    Event::dispatch(new BadgeUnlocked($badge->name, $user));

    expect($provider->requests)->toHaveCount(1)
        ->and($provider->requests[0]->amountKobo)->toBe(30000)
        ->and($provider->requests[0]->bankCode)->toBe('044')
        ->and($provider->requests[0]->accountNumber)->toBe('0123456789')
        ->and($provider->requests[0]->idempotencyKey)->toBe("badge-cashback:{$user->id}:{$badge->id}");

    $transaction = CashbackTransaction::query()->sole();

    expect($transaction->user->is($user))->toBeTrue()
        ->and($transaction->badge->is($badge))->toBeTrue()
        ->and($transaction->amount_kobo)->toBe(30000)
        ->and($transaction->provider)->toBe('flutterwave')
        ->and($transaction->idempotency_key)->toBe("badge-cashback:{$user->id}:{$badge->id}")
        ->and($transaction->status)->toBe('successful')
        ->and($transaction->provider_reference)->toBe('trf_123')
        ->and($transaction->metadata['provider_status'])->toBe('NEW');
});

it('does not send duplicate cashback for a successful transaction', function () {
    $user = createCashbackUserWithPayoutAccount();
    $badge = createCashbackBadge();
    $provider = new FakeCashbackPaymentProvider(PaymentResult::success('NEW', 'trf_123'));

    app()->instance(CashbackPaymentProvider::class, $provider);

    Event::dispatch(new BadgeUnlocked($badge->name, $user));
    Event::dispatch(new BadgeUnlocked($badge->name, $user));

    expect($provider->requests)->toHaveCount(1)
        ->and(CashbackTransaction::query()->count())->toBe(1)
        ->and(CashbackTransaction::query()->sole()->status)->toBe('successful');
});

it('records failed cashback responses', function () {
    $user = createCashbackUserWithPayoutAccount();
    $badge = createCashbackBadge();
    $provider = new FakeCashbackPaymentProvider(PaymentResult::failure('FAILED', 'Transfer failed', [
        'message' => 'Transfer failed',
    ]));

    app()->instance(CashbackPaymentProvider::class, $provider);

    Event::dispatch(new BadgeUnlocked($badge->name, $user));

    $transaction = CashbackTransaction::query()->sole();

    expect($provider->requests)->toHaveCount(1)
        ->and($transaction->status)->toBe('failed')
        ->and($transaction->failure_reason)->toBe('Transfer failed')
        ->and($transaction->metadata['provider_status'])->toBe('FAILED');
});

it('records a failed cashback when the user has no payout account', function () {
    $user = User::factory()->create();
    $badge = createCashbackBadge();
    $provider = new FakeCashbackPaymentProvider(PaymentResult::success('NEW', 'trf_123'));

    app()->instance(CashbackPaymentProvider::class, $provider);

    Event::dispatch(new BadgeUnlocked($badge->name, $user));

    $transaction = CashbackTransaction::query()->sole();

    expect($provider->requests)->toBeEmpty()
        ->and($transaction->status)->toBe('failed')
        ->and($transaction->failure_reason)->toBe('User does not have a payout account.');
});
