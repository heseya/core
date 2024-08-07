<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\DataWithGlobalMetadata;

final class PriceMapUpdatedPricesData extends DataWithGlobalMetadata
{
    /**
     * @param DataCollection<int,PriceMapPricesForProductPartialProductData> $products
     * @param DataCollection<int,PriceMapPricesForProductPartialSchemaOptionData> $schema_options
     */
    public function __construct(
        #[DataCollectionOf(PriceMapPricesForProductPartialProductData::class)]
        public DataCollection $products,
        #[DataCollectionOf(PriceMapPricesForProductPartialSchemaOptionData::class)]
        public DataCollection $schema_options,
    ) {}
}
