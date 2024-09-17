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
     * @param DataCollection<int,PriceMapPricesForProductData> $data
     */
    public function __construct(
        #[WithTransformer(WithoutWrappingTransformer::class)]
        #[DataCollectionOf(PriceMapPricesForProductData::class)]
        public DataCollection $data,
    ) {}
}
