<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeoKeywordsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'keywords' => ['required', 'array'],
        ];
    }
}
