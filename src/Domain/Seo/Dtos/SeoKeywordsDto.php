<?php

declare(strict_types=1);

namespace Domain\Seo\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class SeoKeywordsDto extends Data
{
    /**
     * @param string[] $keywords
     */
    public function __construct(
        public readonly array $keywords,
        public readonly ExcludedModelDto|Optional $excluded,
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'keywords.*' => ['string'],
        ];
    }
}
