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
            'url' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'active' => ['required', new Boolean()],

            'responsive_media' => ['required', 'array'],
            'responsive_media.*' => ['required', 'array'],
            'responsive_media.*.*.min_screen_width' => ['required', 'numeric'],
            'responsive_media.*.*.media' => ['required', 'uuid', 'exists:media,id'],
        ];
    }
}
