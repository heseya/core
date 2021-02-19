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
            'price' => ['required', 'numeric'],
            'hidden' => ['required', 'boolean'],
            'required' => ['required', 'boolean'],
            'min' => ['nullable', 'numeric'],
            'max' => ['nullable', 'numeric'],
            'step' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'default' => ['nullable'],
            'pattern' => ['nullable', 'string'],
            'validation' => ['nullable', 'string'],
        ];
    }
}
