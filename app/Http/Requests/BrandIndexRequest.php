<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'public' => ['nullable', 'boolean'],
        ];
    }
}
