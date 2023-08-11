<?php

declare(strict_types=1);

namespace Support\DtoCasts;

use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataProperty;

final class MoneyCast implements Cast
{
    public function __construct(
        public string $currency_field = 'currency',
    ) {}

    public function cast(DataProperty $property, mixed $value, array $context): Money|Optional
    {
        if ($value === null) {
            return new Optional();
        }

        $currency = Currency::from(Arr::get($context, $this->currency_field, Currency::DEFAULT->value))->toCurrencyInstance();

        return Money::of($value, $currency);
    }
}
