<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int                                      $id
 * @property int                                      $user_id
 * @property string                                   $currency
 * @property string                                   $balance
 * @property string                                   $frozen_balance
 * @property string                                   $available_balance
 * @property Carbon                                   $created_at
 * @property Carbon                                   $updated_at
 *
 * @property-read User                                $user
 * @property-read Collection<int, WalletTransaction>  $transactions
 */
class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'currency',
        'balance',
        'frozen_balance',
    ];

    protected $casts = [
        'balance'        => 'decimal:18',
        'frozen_balance' => 'decimal:18',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // доступный баланс без замороженных средств
    public function getAvailableBalanceAttribute(): string
    {
        return bcsub($this->balance, $this->frozen_balance, 18);
    }
}
