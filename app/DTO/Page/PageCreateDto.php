<?php

declare(strict_types=1);

namespace App\DTO\Page;

use App\DTO\SeoMetadata\SeoMetadataDto;
use App\Rules\Translations;
use App\Utils\Map;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class PageCreateDto extends Data
{
    #[Computed]
    public readonly array|Optional $metadata;

    public function __construct(
        #[Rule(new Translations(['name', 'content_html']))]
        public readonly array $translations,
        public readonly string $slug,
        public readonly bool $public,

        public readonly Optional|SeoMetadataDto $seo,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
    ) {
        $this->metadata = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }

    public static function rules(): array
    {
        return [
            'translations.*.name' => ['string', 'max:255'],
            'translations.*.content_html' => ['string', 'min:1'],
        ];
    }
}
