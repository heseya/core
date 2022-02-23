<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductSetReorderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_sets' => ['required', 'array'],
            'product_sets.*' => ['uuid', 'exists:product_sets,id'],
        ];
    }
}
