<?php

namespace App\Http\Requests;

use App\Enums\SchemaType;
use App\Rules\EnumKey;
use Illuminate\Foundation\Http\FormRequest;

class SchemaStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', new EnumKey(SchemaType::class)],
            'name' => ['required', 'string', 'max:255'],
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

            'options' => ['nullable', 'array'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.price' => ['sometimes', 'required', 'numeric'],
            'options.*.disabled' => ['sometimes', 'required', 'boolean'],

            'options.*.items' => ['nullable', 'array'],
            'options.*.items.*' => ['uuid', 'exists:items,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('required', 'different:hidden', function ($input) {
            return $input->hidden;
        });
    }

    public function messages(): array
    {
        return [
            'required.different' => 'The schema cannot be required if hidden',
        ];
    }
}
