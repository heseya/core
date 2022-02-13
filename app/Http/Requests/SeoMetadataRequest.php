<?php

namespace App\Http\Requests;

use App\Rules\Translations;
use Illuminate\Foundation\Http\FormRequest;

class SeoMetadataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'translations' => [
                'required',
                new Translations(['title', 'description', 'keywords', 'no_index']),
            ],

            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:1000'],
            'translations.*.keywords' => ['nullable', 'array'],
            'translations.*.no_index' => ['nullable', 'boolean'],

//            'title' => ['nullable', 'string', 'max:255'],
//            'description' => ['nullable', 'string', 'max:1000'],
//            'keywords' => ['nullable', 'array'],
//            'no_index' => ['nullable', 'boolean'],

            'published' => ['required', 'array', 'min:1'],
            'published.*' => ['uuid', 'exists:languages,id'],

            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'array'],
            'no_index' => ['nullable', 'boolean'],
        ];
    }
}
