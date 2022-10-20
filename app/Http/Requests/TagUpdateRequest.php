<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TagUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:30'],
            'color' => ['nullable', 'string', 'max:6'],
        ];
    }
}
