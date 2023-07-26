<?php

namespace App\Http\Requests;

use App\Enums\SchemaType;
use App\Rules\EnumKey;
use App\Rules\Translations;
use Illuminate\Foundation\Http\FormRequest;

class SchemaUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'translations' => ['sometimes', new Translations(['name', 'description'])],
            'translations.*.name' => ['sometimes', 'string', 'max:255'],
            'translations.*.description' => ['sometimes', 'nullable', 'string', 'max:255'],

            'published' => ['sometimes', 'array', 'min:1'],
            'published.*' => ['sometimes', 'uuid', 'exists:languages,id'],

            'type' => ['sometimes', 'string', new EnumKey(SchemaType::class)],
            'price' => ['nullable', 'numeric'],
            'hidden' => ['nullable', 'boolean'],
            'required' => ['nullable', 'boolean'],
            'min' => ['nullable', 'numeric', 'min:-100000', 'max:100000'],
            'max' => ['nullable', 'numeric', 'min:-100000', 'max:100000'],
            'step' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'default' => ['nullable'],
            'pattern' => ['nullable', 'string', 'max:255'],
            'validation' => ['nullable', 'string', 'max:255'],

            'options' => ['nullable', 'array'],
            'options.*.translations' => [
                'sometimes',
                new Translations(['name']),
            ],
            'options.*.translations.*.name' => ['string', 'max:255'],

            'options.*.price' => ['sometimes', 'required', 'numeric'],
            'options.*.disabled' => ['sometimes', 'required', 'boolean'],
            'options.*.metadata' => ['array'],
            'options.*.metadata_private' => ['array'],

            'used_schemas' => ['nullable', 'array'],
            'used_schemas.*' => ['uuid', 'exists:schemas,id'],

            'options.*.items' => ['nullable', 'array'],
            'options.*.items.*' => ['uuid', 'exists:items,id'],
        ];
    }
}
