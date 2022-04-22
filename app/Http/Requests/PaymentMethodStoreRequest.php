<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:255'],
            'public' => ['required', 'boolean'],
            'url' => ['required', 'string', 'url'],
        ];
    }
}
