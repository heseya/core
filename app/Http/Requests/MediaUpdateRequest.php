<?php

namespace App\Http\Requests;

use App\Models\Media;
use App\Rules\MediaSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MediaUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Media $media */
        $media = $this->route('media');

        return [
            'alt' => ['nullable', 'string', 'max:100'],
            'slug' => [
                'nullable',
                'string',
                'max:64',
                new MediaSlug($media),
                Rule::unique('media')->ignore($media->slug, 'slug'),
            ],
        ];
    }
}
