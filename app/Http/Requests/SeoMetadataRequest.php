<?php

namespace App\Http\Requests;

use App\Enums\TwitterCardType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class SeoMetadataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'array'],
            'og_image_id' => ['nullable', 'uuid', 'exists:media,id'],
            'twitter_card' => ['nullable', new EnumValue(TwitterCardType::class, false)],
        ];
    }
}
