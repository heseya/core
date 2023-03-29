<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderProductUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_delivered' => ['boolean'],
            'urls' => ['array'],
            'urls.*' => ['nullable', 'url'],
        ];
    }
}
