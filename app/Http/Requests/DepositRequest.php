<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount'       => ['required', 'numeric', 'gt:0'],
            'currency'     => ['required', 'string', 'in:USDT,BTC,ETH'],
            'network'      => ['required', 'string', 'in:ERC20,TRC20,BEP20'],
            'tx_hash'      => ['required', 'string', 'size:64'],
            'from_address' => ['required', 'string'],
            'to_address'   => ['required', 'string'],
        ];
    }
}
