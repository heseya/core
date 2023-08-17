<?php

declare(strict_types=1);

namespace Support\LaravelData\Casts;

use Brick\Money\Currency;
use Domain\Currency\Currency as CurrencyEnum;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataProperty;

final class CurrencyCast implements Cast
{
    /**
     * @param string[] $context
     */
    public function cast(DataProperty $property, mixed $value, array $context): Currency|Optional
    {
        if ($value === null) {
            return new Optional();
        }

        return CurrencyEnum::from($value)->toCurrencyInstance();
    }
}
