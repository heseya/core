<?php

namespace App\DTO\SeoMetadata;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class SeoKeywordsDto extends Data
{
    public function __construct(
        public readonly array $keywords,
        public readonly ExcludedModelDto|Optional $excluded,
    ) {}
}
