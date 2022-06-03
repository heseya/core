<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductSetProductReorderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'size:1'],
            'products.*.order' => ['required', 'integer', 'gte:0'],
            'products.*.id' => ['required', 'uuid', 'exists:products,id'],
        ];
    }
}
