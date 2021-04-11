<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:brands', 'alpha_dash'],
            'public' => 'boolean',
        ];
    }
}
