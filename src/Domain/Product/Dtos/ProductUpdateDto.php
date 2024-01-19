<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\Price\Dtos\PriceDto;
use Domain\Seo\Dtos\SeoMetadataUpdateDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

/**
 * @property MetadataUpdateDto[]|Optional $metadata_computed
 */
final class ProductUpdateDto extends Data
{
    /**
     * @var MetadataUpdateDto[]|Optional $metadata_computed
     */
    #[Computed]
    #[MapOutputName('metadata')]
    public array|Optional $metadata_computed;

    /**
     * @param DataCollection<int,PriceDto>|Optional $prices_base
     * @param string[]|Optional $media
     * @param string[]|Optional $tags
     * @param string[]|Optional $schemas
     * @param string[]|Optional $sets
     * @param string[]|Optional $items
     * @param string[]|Optional $attributes
     * @param string[]|Optional $descriptions
     * @param string[]|Optional $related_sets
     * @param array<string,string[]>|Optional $translations
     * @param string[]|Optional $published
     */
    public function __construct(
        public Optional|string $slug,
        #[DataCollectionOf(PriceDto::class)]
        public DataCollection|Optional $prices_base,
        public bool|Optional $public,
        public bool|Optional $shipping_digital,
        public float|Optional $quantity_step,
        public int|Optional|null $google_product_category,
        public float|Optional|null $purchase_limit_per_user,
        public array|Optional $media,
        public array|Optional $tags,
        public array|Optional $schemas,
        public array|Optional $sets,
        public array|Optional $items,
        public Optional|SeoMetadataUpdateDto $seo,
        public array|Optional $attributes,
        public array|Optional $descriptions,
        public array|Optional $related_sets,
        public array|Optional $translations,
        public array|Optional $published,
        public Optional|ProductBannerMediaUpdateDto|null $banner,
    ) {
        $this->metadata_computed = new Optional();
    }
}
