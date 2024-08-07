<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class PriceMapSchemaPricesUpdateOptionDto extends Data
{
    public function __construct(
        #[Required(), Exists('options', 'id')]
        public string $id,
        #[Required()]
        public string $price,
    ) {}
}
