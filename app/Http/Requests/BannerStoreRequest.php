<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'unique:banners', 'alpha_dash'],
            'url' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'active' => ['required', 'boolean'],

            'responsive_media' => ['required', 'array'],
            'responsive_media.*.min_screen_width' => ['required', 'numeric'],
            'responsive_media.*.media' => ['required', 'uuid', 'exists:media,id'],
        ];
    }
}
