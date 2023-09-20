<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Dtos;

use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Spatie\LaravelData\Data;

final class PriceRangeDto extends Data
{
    public function __construct(
        public readonly Money $start,
        public readonly Money $value,
    ) {}

    /**
     * @param array<string> $data
     *
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            start: Money::of($data['start'], $data['currency']),
            value: Money::of($data['value'], $data['currency']),
        );
    }
}
