<?php

namespace App\DTO\ProductSet;

use App\DTO\SeoMetadata\SeoMetadataDto;
use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ProductSetUpdateDto extends Data
{
    public function __construct(
        #[AlphaDash, Max(255)]
        public Optional|string|null $slug_suffix,
        public bool|Optional $slug_override,
        public bool|Optional $public,
        #[Exists('product_sets', 'id')]
        public Optional|string|null $parent_id,
        #[Exists('media', 'id')]
        public Optional|string|null $cover_id,
        public array|Optional $children_ids,
        public array|Optional $attributes,

        public readonly Optional|SeoMetadataDto $seo,

        #[Rule(new Translations(['name', 'description_html']))]
        public readonly array $translations = [],
    ) {}

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
