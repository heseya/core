<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Support\Dtos\DataWithGlobalMetadata;

final class PriceMapData extends DataWithGlobalMetadata
{
    public function __construct(
        public string $id,
        public string $name,
        public string|null $description,
        public Currency|string $currency,
        public bool $is_net,
        public bool $prices_generated,
    ) {}

    public static function fromModel(PriceMap $priceMap): static
    {
        return new self(
            $priceMap->getKey(),
            $priceMap->name,
            $priceMap->description,
            $priceMap->currency,
            $priceMap->is_net,
            $priceMap->prices_generated,
        );
    }
}
