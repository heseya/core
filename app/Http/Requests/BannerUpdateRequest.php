<?php

namespace App\Http\Requests;

use App\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Banner $banner */
        $banner = $this->route('banner');

        return [
            'slug' => [
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('banners')->ignore($banner->slug, 'slug'),
            ],
            'name' => ['string', 'max:255'],
            'active' => ['boolean'],

            'banner_media' => ['array'],
            'banner_media.*.title' => ['string', 'max:255'],
            'banner_media.*.subtitle' => ['string', 'max:255'],
            'banner_media.*.url' => ['string', 'max:255'],
            'banner_media.*.media' => ['array'],
            'banner_media.*.media.*.min_screen_width' => ['required', 'numeric'],
            'banner_media.*.media.*.media' => ['required', 'uuid', 'exists:media,id'],
        ];
    }
}
