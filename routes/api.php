<?php

use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
    Route::get('/',          [WalletController::class, 'balance']);
    Route::post('/deposit',  [WalletController::class, 'deposit']);
    Route::post('/withdraw', [WalletController::class, 'withdraw']);
});
