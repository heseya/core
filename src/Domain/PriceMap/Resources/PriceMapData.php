<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Domain\Currency\Currency;
use Support\Dtos\DataWithGlobalMetadata;

final class PriceMapData extends DataWithGlobalMetadata
{
    public function __construct(
        public string $id,
        public string $name,
        public string|null $description,
        public Currency|string $currency,
        public bool $is_net,
    ) {}
}
