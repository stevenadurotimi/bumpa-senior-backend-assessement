<?php

namespace App\Models;

use App\Events\PurchaseRecorded;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'reference', 'amount'])]
class Purchase extends Model
{
    /**
     * User who made this purchase.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dispatch the purchase event that starts the reward workflow.
     */
    protected static function booted(): void
    {
        static::created(fn (Purchase $purchase) => PurchaseRecorded::dispatch($purchase));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }
}
