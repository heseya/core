<?php

namespace App\Traits;

use App\Enums\TwitterCardType;
use App\Rules\Translations;
use BenSampo\Enum\Rules\EnumValue;

trait SeoMetadataRules
{
    protected function seoRules(): array
    {
        return [
            'seo.translations' => [
                new Translations(['title', 'description', 'keywords', 'no_index']),
            ],

            'seo.translations.*.title' => ['nullable', 'string', 'max:255'],
            'seo.translations.*.description' => ['nullable', 'string', 'max:1000'],
            'seo.translations.*.keywords' => ['nullable', 'array'],
            'seo.translations.*.no_index' => ['nullable', 'boolean'],

//            'seo.title' => ['nullable', 'string', 'max:255'],
//            'seo.description' => ['nullable', 'string', 'max:1000'],
//            'seo.keywords' => ['nullable', 'array'],
//            'seo.no_index' => ['nullable', 'boolean'],

            'seo.published' => ['array', 'min:1'],
            'seo.published.*' => ['uuid', 'exists:languages,id'],

            'seo.og_image_id' => ['nullable', 'uuid', 'exists:media,id'],
            'seo.twitter_card' => ['nullable', new EnumValue(TwitterCardType::class, false)],
        ];
    }
}
