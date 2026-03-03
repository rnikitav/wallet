<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $wallet_id
 * @property string      $type                  deposit|withdrawal|fee
 * @property string      $status                pending|confirmed|failed|cancelled
 * @property string      $amount
 * @property string      $fee
 * @property string|null $tx_hash
 * @property string|null $network
 * @property string|null $from_address
 * @property string|null $to_address
 * @property int         $confirmations
 * @property int         $required_confirmations
 * @property array|null  $meta
 * @property string      $idempotency_key
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 *
 * @property-read Wallet $wallet
 */
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
