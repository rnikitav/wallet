<?php

namespace App\Jobs;

use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCryptoTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 10;
    public int $backoff = 60;

    public function __construct(private readonly int $transactionId) {}

    public function handle(WalletService $walletService): void
    {
        $transaction = WalletTransaction::find($this->transactionId);

        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        // в реальности тут запрос к блокчейн-ноде (Infura, TronGrid и т.д.)
        $confirmations = $this->fetchConfirmations($transaction->tx_hash, $transaction->network, $transaction->confirmations);

        $transaction->update(['confirmations' => $confirmations]);

        if ($transaction->isConfirmed()) {
            $walletService->confirm($transaction);
            Log::info("Transaction {$transaction->id} confirmed.");
        } else {
            self::dispatch($this->transactionId)
                ->onQueue('crypto')
                ->delay(now()->addMinute());
        }
    }

    private function fetchConfirmations(?string $_txHash, ?string $_network, int $current): int
    {
        // заглушка — заменить на реальный HTTP-запрос к ноде (Infura, TronGrid и т.д.)
        // симулирует постепенный рост подтверждений: +3..7 за каждый блок
        return $current + rand(3, 7);
    }
}
