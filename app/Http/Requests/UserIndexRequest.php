<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'sort' => ['nullable', 'string'],
            'pagination_limit' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
        ];
    }
}
