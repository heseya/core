<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\DataWithGlobalMetadata;
use Support\LaravelData\Transformers\WithoutWrappingTransformer;

final class PriceMapUpdatedPricesData extends DataWithGlobalMetadata
{
    /**
     * @param DataCollection<int,PriceMapPricesForProductPartialProductData> $products
     * @param DataCollection<int,PriceMapPricesForProductPartialSchemaOptionData> $schema_options
     */
    public function __construct(
        #[WithTransformer(WithoutWrappingTransformer::class)]
        #[DataCollectionOf(PriceMapPricesForProductPartialProductData::class)]
        public DataCollection $products,
        #[WithTransformer(WithoutWrappingTransformer::class)]
        #[DataCollectionOf(PriceMapPricesForProductPartialSchemaOptionData::class)]
        public DataCollection $schema_options,
    ) {}
}
