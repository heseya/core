<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductSetAttachRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'products' => ['present', 'array'],
            'products.*' => ['uuid', 'exists:products,id'],
        ];
    }
}
