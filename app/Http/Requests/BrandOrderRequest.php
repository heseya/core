<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'brands' => ['required', 'array'],
            'brands.*' => ['uuid', 'exists:brands,id'],
        ];
    }
}
