<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'user_payout_account_id',
    'badge_id',
    'amount_kobo',
    'provider',
    'idempotency_key',
    'status',
    'provider_reference',
    'failure_reason',
    'metadata',
])]
class CashbackTransaction extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<UserPayoutAccount, $this>
     */
    public function payoutAccount(): BelongsTo
    {
        return $this->belongsTo(UserPayoutAccount::class, 'user_payout_account_id');
    }

    /**
     * @return BelongsTo<Badge, $this>
     */
    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'metadata' => 'array',
        ];
    }
}
