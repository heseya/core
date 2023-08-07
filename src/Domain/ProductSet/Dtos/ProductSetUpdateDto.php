<?php

declare(strict_types=1);

namespace Domain\ProductSet\Dtos;

use App\DTO\SeoMetadata\SeoMetadataDto;
use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class ProductSetUpdateDto extends Data
{
    /**
     * @param string[]|Optional $children_ids
     * @param string[]|Optional $attributes
     * @param array<string, array<string, string>> $translations
     */
    public function __construct(
        #[AlphaDash, Max(255)]
        public readonly Optional|string|null $slug_suffix,
        public readonly bool|Optional $slug_override,
        public readonly bool|Optional $public,
        #[Exists('product_sets', 'id')]
        public readonly Optional|string|null $parent_id,
        #[Exists('media', 'id')]
        public readonly Optional|string|null $cover_id,
        public readonly array|Optional $children_ids,
        public readonly array|Optional $attributes,

        public readonly Optional|SeoMetadataDto $seo,

        #[Rule(new Translations(['name', 'description_html']))]
        public readonly array $translations = [],
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(): array
    {
        return [
            'translations.*.name' => ['sometimes', 'string', 'max:255'],
            'translations.*.description_html' => ['sometimes', 'string', 'max:255'],
            'children_ids.*' => ['uuid', 'exists:product_sets,id'],
            'attributes.*' => ['uuid', 'exists:attributes,id'],
        ];
    }
}
