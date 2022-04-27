<?php

namespace App\Http\Requests;

use App\Models\Banner;
use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerUpdateRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'active',
    ];

    public function rules(): array
    {
        /** @var Banner $banner */
        $banner = $this->route('banner');

        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('banners')->ignore($banner->slug, 'slug'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'active' => ['required', new Boolean()],

            'banner_media' => ['required', 'array'],
            'banner_media.*.title' => ['required', 'string', 'max:255'],
            'banner_media.*.subtitle' => ['required', 'string', 'max:255'],
            'banner_media.*.url' => ['required', 'string', 'max:255'],
            'banner_media.*.responsive_media' => ['required', 'array'],
            'banner_media.*.responsive_media.*.min_screen_width' => ['required', 'numeric'],
            'banner_media.*.responsive_media.*.media' => ['required', 'uuid', 'exists:media,id'],
        ];
    }
}
