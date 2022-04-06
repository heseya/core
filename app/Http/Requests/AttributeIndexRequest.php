<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttributeIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'global' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
        ];
    }
}
