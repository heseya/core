<?php

namespace App\Traits;

use App\Enums\TwitterCardType;
use App\Rules\Translations;
use Illuminate\Validation\Rules\Enum;

trait SeoRules
{
    public function seoRules(string $prefix = 'seo.'): array
    {
        return [
            "{$prefix}translations" => [
                new Translations(['title', 'description', 'keywords', 'no_index']),
            ],

            "{$prefix}translations.*.title" => ['nullable', 'string', 'max:255'],
            "{$prefix}translations.*.description" => ['nullable', 'string', 'max:1000'],
            "{$prefix}translations.*.keywords" => ['nullable', 'array'],
            "{$prefix}translations.*.no_index" => ['nullable', 'boolean'],

            "{$prefix}published" => ['array', 'min:1'],
            "{$prefix}published.*" => ['uuid', 'exists:languages,id'],

            "{$prefix}og_image_id" => ['nullable', 'uuid', 'exists:media,id'],
            "{$prefix}twitter_card" => ['nullable', new Enum(TwitterCardType::class)],
            "{$prefix}header_tags" => ['nullable', 'array'],
        ];
    }
}
