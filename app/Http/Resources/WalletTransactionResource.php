<?php

namespace App\Http\Resources;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property  WalletTransaction $resource
 */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;
        return [
            'id'      => $resource->id,
            'type'    => $resource->type,
            'status'  => $resource->status,
            'amount'  => $resource->amount,
            'fee'     => $resource->fee,
            'network' => $resource->network,
        ];
    }
}