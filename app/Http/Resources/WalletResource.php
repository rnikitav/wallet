<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property  Wallet $resource
 */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;
        return [
            'currency'          => $resource->currency,
            'balance'           => $resource->balance,
            'frozen_balance'    => $resource->frozen_balance,
            'available_balance' => $resource->available_balance,
        ];
    }
}