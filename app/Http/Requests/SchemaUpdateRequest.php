<?php

namespace App\Http\Requests;

use App\Enums\SchemaType;
use App\Rules\Boolean;
use App\Rules\EnumKey;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class SchemaUpdateRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'options.*.disabled',
        'hidden',
        'required',
    ];

    public function rules(): array
    {
        return [
            'type' => ['string', new EnumKey(SchemaType::class)],
            'name' => ['string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'hidden' => ['nullable', new Boolean()],
            'required' => ['nullable', new Boolean()],
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
            'options.*.disabled' => ['sometimes', 'required', new Boolean()],

            'options.*.items' => ['nullable', 'array'],
            'options.*.items.*' => ['uuid', 'exists:items,id'],
        ];
    }
}
