<?php

namespace App\Http\Requests;

use App\Rules\Translations;
use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class StatusCreateRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
            'translations' => [
                'required',
                new Translations(['name', 'description']),
            ],
            'translations.*.name' => ['string', 'max:60'],
            'translations.*.description' => ['string', 'max:255', 'nullable'],

            'published' => ['required', 'array', 'min:1'],
            'published.*' => ['uuid', 'exists:languages,id'],

            'color' => ['required', 'string', 'size:6'],
            'cancel' => ['boolean'],
            'hidden' => ['boolean'],
            'no_notifications' => ['boolean'],
        ],
    );
    }
}
