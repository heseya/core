<?php

namespace App\Dtos;

use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domains\Currency\Currency;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;

class PriceRangeDto extends Dto
{
    public function __construct(
        public readonly Money $start,
        public readonly Money $value,
    ) {}

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws DtoException
     */
    public static function fromData(string $value, string $start, string $currency): self
    {
        $currency = Currency::from($currency);

        return new self(
            start: Money::of($start, $currency->value),
            value: Money::of($value, $currency->value),
        );
    }
}
