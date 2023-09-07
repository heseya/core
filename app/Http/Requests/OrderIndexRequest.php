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
            'digital_shipping_method_id' => ['nullable', 'uuid'],
            'sales_channel_id' => ['nullable', 'uuid'],

            'paid' => ['sometimes', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
            'payments' => ['string', 'max:255'],
        ];
    }
}
