<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PageReorderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pages' => ['required', 'array'],
            'pages.*' => ['uuid', 'exists:pages,id'],
        ];
    }
}
