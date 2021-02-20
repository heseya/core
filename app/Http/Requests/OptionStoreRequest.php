<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OptionStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'disabled' => ['required', 'boolean'],
            'schema_id' => ['required', 'uuid', 'exists:schemas,id'],

            'items' => ['array'],
            'items.*' => ['uuid', 'exists:items,id'],
        ];
    }
}
