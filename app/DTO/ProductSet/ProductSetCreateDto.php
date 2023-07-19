<?php

namespace App\DTO\ProductSet;

use App\DTO\SeoMetadata\SeoMetadataDto;
use App\Rules\Translations;
use App\Utils\Map;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ProductSetCreateDto extends Data
{
    #[Computed]
    public readonly array|Optional $metadata;

    public function __construct(
        #[Rule(new Translations(['name', 'description_html']))]
        public readonly array $translations,
        #[AlphaDash, Max(255)]
        public readonly string|null $slug_suffix,
        public readonly bool $slug_override,
        public readonly bool|Optional $public,

        public readonly Optional|SeoMetadataDto $seo,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,

        #[Exists('product_sets', 'id')]
        public readonly string|null $parent_id = null,
        #[Exists('media', 'id')]
        public readonly string|null $cover_id = null,
        public readonly array $children_ids = [],
        public readonly array $attributes = [],
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
            'translations.*.description_html' => ['string', 'max:255'],
            'children_ids.*' => ['uuid', 'exists:product_sets,id'],
            'attributes.*' => ['uuid', 'exists:attributes,id'],
        ];
    }
}
