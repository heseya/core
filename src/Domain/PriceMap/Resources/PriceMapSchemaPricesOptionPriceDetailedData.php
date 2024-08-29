<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Support\Dtos\DataWithGlobalMetadata;

final class PriceMapSchemaPricesOptionPriceDetailedData extends DataWithGlobalMetadata
{
    public function __construct(
        public string $price_map_id,
        public string $price_map_name,
        public bool $is_net,
        public string $currency,
        public string $price,
    ) {}

    public static function fromModel(PriceMapSchemaOptionPrice $price): static
    {
        return new self(
            $price->price_map_id,
            $price->map?->name ?? '',
            $price->is_net,
            $price->currency->value,
            (string) $price->value->getAmount(),
        );
    }
}
