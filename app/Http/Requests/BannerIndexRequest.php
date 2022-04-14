<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerIndexRequest extends FormRequest
{
    public function rules()
    {
        return [
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
        ];
    }
}
