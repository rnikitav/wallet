<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount'     => ['required', 'numeric', 'gt:0'],
            'currency'   => ['required', 'string', 'in:USDT,BTC,ETH'],
            'network'    => ['required', 'string', 'in:ERC20,TRC20,BEP20'],
            'to_address' => ['required', 'string'],
        ];
    }
}
