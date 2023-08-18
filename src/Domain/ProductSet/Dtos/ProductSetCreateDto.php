<?php

declare(strict_types=1);

namespace Domain\ProductSet\Dtos;

use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\Seo\Dtos\SeoMetadataDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\Utils\Map;

final class ProductSetCreateDto extends Data
{
    /**
     * @var Optional|MetadataUpdateDto[]
     */
    #[Computed]
    #[MapOutputName('metadata')]
    public readonly array|Optional $metadata_computed;

    /**
     * @param array<string, array<string, string>> $translations
     * @param string[] $published
     * @param array<string, string>|Optional $metadata_public
     * @param array<string, string>|Optional $metadata_private
     * @param string[] $children_ids
     * @param string[] $attributes
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Rule(new Translations(['name', 'description_html']))]
        public readonly array $translations,
        public readonly array $published,
        #[AlphaDash, Max(255)]
        public readonly string|null $slug_suffix,
        public readonly bool $slug_override,
        public readonly bool $public,

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
        $this->metadata_computed = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }

    /**
     * @return array<string, string[]>
     */
    public static function rules(): array
    {
        return [
            'translations.*.name' => ['string', 'max:255'],
            'translations.*.description_html' => ['nullable', 'string', 'max:255'],
            'children_ids' => ['nullable'],
            'children_ids.*' => ['uuid', 'exists:product_sets,id'],
            'attributes' => ['nullable'],
            'attributes.*' => ['uuid', 'exists:attributes,id'],
        ];
    }
}
