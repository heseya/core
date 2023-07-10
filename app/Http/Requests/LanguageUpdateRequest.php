<?php

namespace App\Http\Requests;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Language $language */
        $language = $this->route('language');

        return [
            'iso' => [
                'string',
                'max:16',
                Rule::unique('languages', 'iso')->ignore($language->getKey()),
            ],
            'name' => ['string', 'max:80'],
            'default' => ['boolean'],
            'hidden' => ['boolean'],
        ];
    }
}
