<?php

namespace App\Http\Requests;

use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class BannerStoreRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'slug' => ['required', 'string', 'max:255', 'unique:banners', 'alpha_dash'],
                'name' => ['required', 'string', 'max:255'],
                'active' => ['required', 'boolean'],

                'banner_media' => ['required', 'array'],
                'banner_media.*.title' => ['required', 'string', 'max:255'],
                'banner_media.*.subtitle' => ['required', 'string', 'max:255'],
                'banner_media.*.url' => ['required', 'string', 'max:255'],
                'banner_media.*.media' => ['required', 'array'],
                'banner_media.*.media.*.min_screen_width' => ['required', 'numeric'],
                'banner_media.*.media.*.media' => ['required', 'uuid', 'exists:media,id'],
            ]
        );
    }
}
