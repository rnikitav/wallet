<?php

namespace App\Exceptions;

use RuntimeException;

class WalletException extends RuntimeException
{
    public static function insufficientFunds(): self
    {
        return new self('Insufficient funds on balance.', 422);
    }

    public static function duplicateTransaction(): self
    {
        return new self('Duplicate transaction detected.', 409);
    }

    public static function walletNotFound(): self
    {
        return new self('Wallet not found.', 404);
    }
}
