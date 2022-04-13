<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'string',
                'max:255',
                Rule::unique('items')->ignore($this->route('item')->sku, 'sku'),
            ],
        ];
    }
}
