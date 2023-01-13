<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
        ];
    }
}
