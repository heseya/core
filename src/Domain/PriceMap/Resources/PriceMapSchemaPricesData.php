<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\DataWithGlobalMetadata;
use Support\LaravelData\Transformers\WithoutWrappingTransformer;

final class PriceMapSchemaPricesData extends DataWithGlobalMetadata
{
    protected static string $_collectionClass = PriceMapSchemaPricesDataCollection::class;

    /**
     * @param DataCollection<int,PriceMapSchemaPricesOptionPriceData> $options
     */
    public function __construct(
        public string $price_map_id,
        public string $price_map_name,
        public bool $is_net,
        public string $currency,
        #[WithTransformer(WithoutWrappingTransformer::class)]
        #[DataCollectionOf(PriceMapSchemaPricesOptionPriceData::class)]
        public DataCollection $options,
    ) {}
}
