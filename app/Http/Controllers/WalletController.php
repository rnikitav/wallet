<?php

namespace App\Http\Controllers;

use App\Exceptions\WalletException;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function balance(Request $request): AnonymousResourceCollection
    {
        $wallets = Wallet::where('user_id', $request->user()->id)->get();

        return WalletResource::collection($wallets);
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

            return (new WalletTransactionResource($transaction))
                ->additional(['message' => 'Deposit pending confirmation.'])
                ->response()
                ->setStatusCode(201);

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

            return (new WalletTransactionResource($transaction))
                ->additional(['message' => 'Withdrawal pending processing.'])
                ->response()
                ->setStatusCode(201);

        } catch (WalletException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
}