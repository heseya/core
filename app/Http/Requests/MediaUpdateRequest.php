<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'alt' => ['nullable', 'string', 'max:100'],
            'slug' => ['string', 'max:64'],
        ];
    }
}
