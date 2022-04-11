<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsentStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description_html' => ['required', 'string'],
            'required' => ['required', 'boolean'],
        ];
    }
}
