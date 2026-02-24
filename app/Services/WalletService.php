<?php

namespace App\Services;

use App\Exceptions\WalletException;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Jobs\ProcessCryptoTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    private const string FEE_PERCENT = '0.01';

    public function deposit(
        int    $userId,
        string $amount,
        string $currency,
        string $network,
        string $txHash,
        string $fromAddress,
        string $toAddress,
    ): WalletTransaction {
        $idempotencyKey = hash('sha256', $txHash . $network);

        if (WalletTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
            throw WalletException::duplicateTransaction();
        }

        return DB::transaction(function () use (
            $userId, $amount, $currency, $network,
            $txHash, $fromAddress, $toAddress, $idempotencyKey
        ) {
            $wallet = $this->getOrCreateWallet($userId, $currency);

            $transaction = WalletTransaction::create([
                'wallet_id'              => $wallet->id,
                'type'                   => 'deposit',
                'status'                 => 'pending',
                'amount'                 => $amount,
                'fee'                    => '0',
                'tx_hash'                => $txHash,
                'network'                => $network,
                'from_address'           => $fromAddress,
                'to_address'             => $toAddress,
                'confirmations'          => 0,
                'required_confirmations' => $this->getRequiredConfirmations($network),
                'idempotency_key'        => $idempotencyKey,
            ]);

            ProcessCryptoTransaction::dispatch($transaction->id)
                ->onQueue('crypto')
                ->delay(now()->addSeconds(30));

            return $transaction;
        });
    }

    public function withdraw(
        int    $userId,
        string $amount,
        string $currency,
        string $network,
        string $toAddress,
    ): WalletTransaction {
        return DB::transaction(function () use ($userId, $amount, $currency, $network, $toAddress) {
            $wallet = Wallet::where('user_id', $userId)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            $fee   = bcmul($amount, self::FEE_PERCENT, 18);
            $total = bcadd($amount, $fee, 18);

            if (bccomp($wallet->available_balance, $total, 18) < 0) {
                throw WalletException::insufficientFunds();
            }

            $wallet->increment('frozen_balance', $total);

            $idempotencyKey = Str::uuid()->toString();

            $transaction = WalletTransaction::create([
                'wallet_id'              => $wallet->id,
                'type'                   => 'withdrawal',
                'status'                 => 'pending',
                'amount'                 => $amount,
                'fee'                    => $fee,
                'network'                => $network,
                'to_address'             => $toAddress,
                'required_confirmations' => $this->getRequiredConfirmations($network),
                'idempotency_key'        => $idempotencyKey,
            ]);

            ProcessCryptoTransaction::dispatch($transaction->id)
                ->onQueue('crypto');

            return $transaction;
        });
    }

    public function confirm(WalletTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $wallet = Wallet::lockForUpdate()->find($transaction->wallet_id);

            if ($transaction->type === 'deposit') {
                $wallet->increment('balance', $transaction->amount);
            }

            if ($transaction->type === 'withdrawal') {
                $total = bcadd($transaction->amount, $transaction->fee, 18);
                $wallet->decrement('balance', $total);
                $wallet->decrement('frozen_balance', $total);
            }

            $transaction->update(['status' => 'confirmed']);
        });
    }

    public function cancel(WalletTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            if ($transaction->type === 'withdrawal' && $transaction->status === 'pending') {
                $wallet = Wallet::lockForUpdate()->find($transaction->wallet_id);
                $total  = bcadd($transaction->amount, $transaction->fee, 18);
                $wallet->decrement('frozen_balance', $total);
            }

            $transaction->update(['status' => 'cancelled']);
        });
    }

    private function getOrCreateWallet(int $userId, string $currency): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId, 'currency' => $currency],
            ['balance' => '0', 'frozen_balance' => '0']
        );
    }

    private function getRequiredConfirmations(string $network): int
    {
        return match (strtoupper($network)) {
            'ERC20' => 12,
            'TRC20' => 20,
            'BEP20' => 15,
            default => 6,
        };
    }
}
