<?php

namespace Domains\Currency;

use Spatie\LaravelData\Data;

class CurrencyDto extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public int $decimal_places,
    ) {}
}
