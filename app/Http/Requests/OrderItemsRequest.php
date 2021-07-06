<?php

namespace App\Http\Requests;

use App\Rules\ProductPublic;
use Illuminate\Foundation\Http\FormRequest;

class OrderItemsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', new ProductPublic()],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            'items.*.schemas' => ['nullable', 'array'],
        ];
    }
}
