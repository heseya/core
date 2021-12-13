<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'alt' => ['string', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:64'],
        ];
    }
}
