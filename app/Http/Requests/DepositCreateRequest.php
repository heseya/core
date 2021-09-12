<?php

namespace App\Http\Requests;

use App\Rules\Decimal;
use Illuminate\Foundation\Http\FormRequest;

class DepositCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'quantity' => array_merge(['required'], Decimal::defaults()),
        ];
    }
}
