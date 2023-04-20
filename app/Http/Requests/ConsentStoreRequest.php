<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsentStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description_html' => ['required', 'string', 'max:65000'],
            'required' => ['required', 'boolean'],
        ];
    }
}
