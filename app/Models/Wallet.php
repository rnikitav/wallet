<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
