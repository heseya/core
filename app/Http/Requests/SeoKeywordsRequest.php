<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeoKeywordsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'keywords' => ['required', 'array'],
            'excluded.id' => ['required_with_all:excluded', 'uuid'],
            'excluded.model' => ['required_with_all:excluded', 'string'],
        ];
    }
}
