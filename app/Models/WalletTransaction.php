<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'status',
        'amount',
        'fee',
        'tx_hash',
        'network',
        'from_address',
        'to_address',
        'confirmations',
        'required_confirmations',
        'meta',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'decimal:18',
        'fee'    => 'decimal:18',
        'meta'   => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmations >= $this->required_confirmations;
    }
}
