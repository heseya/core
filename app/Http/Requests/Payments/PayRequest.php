<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class PayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'continue_url' => ['required', 'string'],
        ];
    }
}
