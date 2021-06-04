<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string'],

            'status_id' => ['nullable', 'uuid'],
            'shipping_method_id' => ['nullable', 'uuid'],

            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
