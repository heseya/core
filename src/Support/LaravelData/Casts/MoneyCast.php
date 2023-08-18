<?php

declare(strict_types=1);

namespace Support\LaravelData\Casts;

use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataProperty;

final class MoneyCast implements Cast
{
    public function __construct(
        public string $currency_field = 'currency',
    ) {}

    /**
     * @param string[] $context
     */
    public function cast(DataProperty $property, mixed $value, array $context): Money|Optional|Uncastable
    {
        if ($value === null) {
            return new Optional();
        }

        $currency = Arr::get($context, $this->currency_field, Currency::DEFAULT);

        $currency = match (true) {
            $currency instanceof Currency => $currency,
            is_string($currency) => Currency::from($currency),
            default => Uncastable::create(),
        };

        return $currency instanceof Uncastable ? $currency : Money::of($value, $currency->value);
    }
}
