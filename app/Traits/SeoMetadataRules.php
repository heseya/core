<?php

namespace App\Traits;

use App\Enums\TwitterCardType;
use BenSampo\Enum\Rules\EnumValue;

trait SeoMetadataRules
{
    protected function seoRules(): array
    {
        return [
            'seo.title' => ['nullable', 'string', 'max:255'],
            'seo.description' => ['nullable', 'string', 'max:1000'],
            'seo.keywords' => ['nullable', 'array'],
            'seo.og_image' => ['nullable', 'uuid', 'exists:media,id'],
            'seo.twitter_card' => ['nullable', new EnumValue(TwitterCardType::class, false)],
        ];
    }
}
