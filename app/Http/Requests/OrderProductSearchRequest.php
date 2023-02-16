<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderProductSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipping_digital' => ['nullable', 'boolean'],
        ];
    }
}
