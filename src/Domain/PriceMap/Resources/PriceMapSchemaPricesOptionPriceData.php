<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Support\Dtos\DataWithGlobalMetadata;

final class PriceMapSchemaPricesOptionPriceData extends DataWithGlobalMetadata
{
    public function __construct(
        public string $id,
        public string $price,
    ) {}

    public static function fromModel(PriceMapSchemaOptionPrice $price): static
    {
        return new self(
            $price->option_id,
            (string) $price->value->getAmount(),
        );
    }
}
