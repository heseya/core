<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\DataWithGlobalMetadata;

final class PriceMapSchemaPricesData extends DataWithGlobalMetadata
{
    /**
     * @param DataCollection<int,PriceMapSchemaPricesOptionPriceData> $options
     */
    public function __construct(
        public string $price_map_id,
        public string $price_map_name,
        public bool $is_net,
        public string $currency,
        #[DataCollectionOf(PriceMapSchemaPricesOptionPriceData::class)]
        public DataCollection $options,
    ) {}
}
