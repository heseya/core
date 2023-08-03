<?php

declare(strict_types=1);

namespace Domain\Currency;

use Spatie\LaravelData\Data;

class CurrencyDto extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public int $decimal_places,
    ) {}
}
