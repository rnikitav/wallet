<?php

namespace App\Http\Controllers;

use App\Exceptions\WalletException;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function balance(Request $request): JsonResponse
    {
        $wallets = Wallet::where('user_id', $request->user()->id)->get();

        return response()->json([
            'wallets' => $wallets->map(fn($w) => [
                'currency'          => $w->currency,
                'balance'           => $w->balance,
                'frozen_balance'    => $w->frozen_balance,
                'available_balance' => $w->available_balance,
            ]),
        ]);
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        try {
            $transaction = $this->walletService->deposit(
                userId:      $request->user()->id,
                amount:      $request->input('amount'),
                currency:    $request->input('currency'),
                network:     $request->input('network'),
                txHash:      $request->input('tx_hash'),
                fromAddress: $request->input('from_address'),
                toAddress:   $request->input('to_address'),
            );

            return response()->json([
                'message'        => 'Deposit pending confirmation.',
                'transaction_id' => $transaction->id,
                'status'         => $transaction->status,
            ], 201);

        } catch (WalletException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        try {
            $transaction = $this->walletService->withdraw(
                userId:    $request->user()->id,
                amount:    $request->input('amount'),
                currency:  $request->input('currency'),
                network:   $request->input('network'),
                toAddress: $request->input('to_address'),
            );

            return response()->json([
                'message'        => 'Withdrawal pending processing.',
                'transaction_id' => $transaction->id,
                'status'         => $transaction->status,
                'fee'            => $transaction->fee,
            ], 201);

        } catch (WalletException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
}
