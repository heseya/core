<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TagIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
        ];
    }
}
