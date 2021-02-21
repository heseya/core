<?php

namespace App\Http\Requests;

use App\Models\Schema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchemaStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_values(Schema::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'hidden' => ['nullable', 'boolean'],
            'required' => ['nullable', 'boolean'],
            'min' => ['nullable', 'numeric'],
            'max' => ['nullable', 'numeric'],
            'step' => ['nullable', 'numeric'],
            'default' => ['nullable'],
            'pattern' => ['nullable', 'string'],
            'validation' => ['nullable', 'string'],

            'options' => ['nullable', 'array'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.price' => ['required', 'numeric'],
            'options.*.disabled' => ['required', 'boolean'],

            'options.*.items' => ['nullable', 'array'],
            'options.*.items.*' => ['uuid', 'exists:items,id'],
        ];
    }
}
