<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductPricesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }
}
