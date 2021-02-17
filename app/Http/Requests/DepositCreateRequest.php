<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric'],
        ];
    }
}
