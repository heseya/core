<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageTemplateIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
        ];
    }
}
