<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\Price\Dtos\PriceDto;
use Domain\Seo\Dtos\SeoMetadataCreateDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Support\Utils\Map;

/**
 * @property MetadataUpdateDto[]|Optional $metadata_computed
 */
final class ProductCreateDto extends Data
{
    /**
     * @var MetadataUpdateDto[]|Optional $metadata_computed
     */
    #[Computed]
    #[MapOutputName('metadata')]
    public array|Optional $metadata_computed;

    /**
     * @param DataCollection<int,PriceDto> $prices_base
     * @param string[]|Optional $media
     * @param string[]|Optional $tags
     * @param string[]|Optional $schemas
     * @param string[]|Optional $sets
     * @param string[]|Optional $items
     * @param string[]|Optional $attributes
     * @param string[]|Optional $descriptions
     * @param string[]|Optional $related_sets
     * @param string[]|Optional $metadata_public
     * @param string[]|Optional $metadata_private
     * @param array<string,string[]> $translations
     * @param string[] $published
     */
    public function __construct(
        public string $slug,
        #[DataCollectionOf(PriceDto::class)]
        public DataCollection $prices_base,
        public bool $public,
        public bool $shipping_digital,
        public Optional|string $id,
        public float|Optional $quantity_step,
        public int|Optional|null $google_product_category,
        public float|Optional|null $purchase_limit_per_user,
        public array|Optional $media,
        public array|Optional $tags,
        public array|Optional $schemas,
        public array|Optional $sets,
        public array|Optional $items,
        public Optional|SeoMetadataCreateDto $seo,
        public array|Optional $attributes,
        public array|Optional $descriptions,
        public array|Optional $related_sets,
        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
        #[Rule(new Translations(['name']))]
        public array $translations,
        public array $published,
        public Optional|ProductBannerMediaCreateDto|null $banner,
    ) {
        $this->metadata_computed = Map::toMetadata($metadata_public, $metadata_private);
    }
}
