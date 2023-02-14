<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use Illuminate\Foundation\Http\FormRequest;

class OrderProductUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_delivered' => [new Boolean()],
            'urls' => ['array'],
            'urls.*' => ['nullable', 'url'],
        ];
    }
}
