<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MediaUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'alt' => ['nullable', 'string', 'max:100'],
            'slug' => [
                'string',
                'max:64',
                Rule::unique('media')->ignore($this->route('media')->slug, 'slug'),
            ],
        ];
    }
}
