<?php

namespace App\Dtos;

use App\Enums\Currency;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Heseya\Dto\Dto;

class PriceDto extends Dto
{
    public readonly Money $value;

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function __construct(
        string $value,
    ) {
        $this->value = Money::of($value, Currency::DEFAULT->value);
    }
}
