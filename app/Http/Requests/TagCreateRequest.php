<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TagCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['uuid'],

            'name' => ['required', 'string', 'max:30'],
            'color' => ['nullable', 'string', 'max:6'],
        ];
    }
}
