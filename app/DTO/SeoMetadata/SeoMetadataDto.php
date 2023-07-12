<?php

namespace App\DTO\SeoMetadata;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class SeoMetadataDto extends Data
{
    public function __construct(
        #[Rule(new Translations(['title', 'description', 'keywords', 'no_index']))]
        public array $translations,
        public Optional|string|null $twitter_card,
        public Optional|string|null $og_image,
        public Optional|string|null $model_id,
        public Optional|string|null $model_type,
        public bool|Optional $no_index,
        public array|Optional|null $header_tags,
        /** @var array<string> */
        public array $published,
    ) {}
}
