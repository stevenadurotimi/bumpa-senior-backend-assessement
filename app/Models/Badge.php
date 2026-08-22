<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'required_achievements_count', 'sort_order'])]
class Badge extends Model
{
    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<CashbackTransaction, $this>
     */
    public function cashbackTransactions(): HasMany
    {
        return $this->hasMany(CashbackTransaction::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_achievements_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
