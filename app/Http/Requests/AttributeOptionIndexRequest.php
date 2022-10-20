<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttributeOptionIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['string'],

            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
            'name' => ['nullable', 'string'],
        ];
    }
}
