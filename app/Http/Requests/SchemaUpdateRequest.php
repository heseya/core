<?php

namespace App\Http\Requests;

use App\Enums\SchemaType;
use App\Rules\EnumKey;
use Illuminate\Foundation\Http\FormRequest;

class SchemaUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['string', new EnumKey(SchemaType::class)],
            'name' => ['string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'hidden' => ['nullable', 'boolean'],
            'required' => ['nullable', 'boolean'],
            'min' => ['nullable', 'numeric', 'min:-100000', 'max:100000'],
            'max' => ['nullable', 'numeric', 'min:-100000', 'max:100000'],
            'step' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'default' => ['nullable'],
            'pattern' => ['nullable', 'string', 'max:255'],
            'validation' => ['nullable', 'string', 'max:255'],

            'used_schemas' => ['nullable', 'array'],
            'used_schemas.*' => ['uuid', 'exists:schemas,id'],

            'options' => ['nullable', 'array'],
            'options.*.name' => ['string', 'max:255'],
            'options.*.price' => ['sometimes', 'numeric'],
            'options.*.disabled' => ['sometimes', 'required', 'boolean'],

            'options.*.items' => ['nullable', 'array'],
            'options.*.items.*' => ['uuid', 'exists:items,id'],
        ];
    }
}
