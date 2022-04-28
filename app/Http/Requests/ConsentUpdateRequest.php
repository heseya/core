<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsentUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:100'],
            'description_html' => ['string'],
            'required' => ['boolean'],
        ];
    }
}
