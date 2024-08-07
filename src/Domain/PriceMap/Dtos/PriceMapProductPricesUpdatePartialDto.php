<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class PriceMapProductPricesUpdatePartialDto extends Data
{
    public function __construct(
        #[Required(), Exists('price_maps', 'id')]
        public string $price_map_id,
        #[Required()]
        public string $price,
    ) {}
}
