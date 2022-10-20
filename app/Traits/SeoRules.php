<?php

namespace App\Traits;

use App\Enums\TwitterCardType;
use App\Rules\Boolean;
use BenSampo\Enum\Rules\EnumValue;

trait SeoRules
{
    public function seoRules(string $prefix = 'seo.'): array
    {
        return [
            "{$prefix}title" => ['nullable', 'string', 'max:255'],
            "{$prefix}description" => ['nullable', 'string', 'max:1000'],
            "{$prefix}keywords" => ['nullable', 'array'],
            "{$prefix}og_image_id" => ['nullable', 'uuid', 'exists:media,id'],
            "{$prefix}twitter_card" => ['nullable', new EnumValue(TwitterCardType::class, false)],
            "{$prefix}no_index" => [new Boolean()],
        ];
    }
}
