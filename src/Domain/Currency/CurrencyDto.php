<?php

declare(strict_types=1);

namespace Domain\Currency;

use Spatie\LaravelData\Data;

final class CurrencyDto extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public int $decimal_places,
    ) {}

    public static function fromEnum(Currency $currency): self
    {
        $currencyDetails = $currency->toCurrencyInstance();

        return new self(
            $currencyDetails->getName(),
            $currencyDetails->getCurrencyCode(),
            $currencyDetails->getDefaultFractionDigits(),
        );
    }
}
