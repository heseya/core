<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use Illuminate\Foundation\Http\FormRequest;

class OrderProductSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipping_digital' => ['nullable', new Boolean()],
        ];
    }
}
