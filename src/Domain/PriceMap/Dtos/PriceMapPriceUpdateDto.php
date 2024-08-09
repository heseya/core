<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class PriceMapPriceUpdateDto extends Data
{
    public function __construct(
        #[Required()]
        public string $id,
        #[Required()]
        public float|int|string $value,
    ) {}
}
