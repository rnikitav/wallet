<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
    Route::get('/',          [WalletController::class, 'balance']);
    Route::post('/deposit',  [WalletController::class, 'deposit']);
    Route::post('/withdraw', [WalletController::class, 'withdraw']);
});