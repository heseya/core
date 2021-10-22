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
            'min' => ['nullable', 'numeric', 'min:-100000', 'max:100000'],
            'max' => ['nullable', 'numeric', 'min:-100000', 'max:100000'],
            'step' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'default' => ['nullable', 'string', 'max:255'],
            'pattern' => ['nullable', 'string', 'max:255'],
            'validation' => ['nullable', 'string', 'max:255'],

            'options' => ['nullable', 'array'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.price' => ['nullable', 'numeric'],
            'options.*.disabled' => ['nullable', 'boolean'],

            'options.*.items' => ['nullable', 'array'],
            'options.*.items.*' => ['uuid', 'exists:items,id'],
        ];
    }
}
