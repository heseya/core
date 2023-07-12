<?php

namespace App\DTO\Page;

use App\DTO\Metadata\Metadata;
use App\DTO\SeoMetadata\SeoMetadataDto;
use App\Rules\Translations;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class PageCreateDto extends Data
{
    use Metadata;

    public function __construct(
        #[Rule(new Translations(['name', 'content_html']))]
        public array $translations,
        public string $slug,
        public bool $public,

        public Optional|SeoMetadataDto $seo,

        #[MapInputName('metadata')]
        public array|Optional $metadata_public,
        public array|Optional $metadata_private,
    ) {
        $this->mapMetadata(
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
