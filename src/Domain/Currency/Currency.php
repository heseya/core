<?php

declare(strict_types=1);

namespace Domain\Currency;

use App\Enums\Traits\EnumTrait;
use Brick\Money\Currency as CurrencyInstance;
use Brick\Money\Exception\UnknownCurrencyException;

enum Currency: string
{
    use EnumTrait;

    public const DEFAULT = self::PLN;

    case PLN = 'PLN';
    case GBP = 'GBP';
    case EUR = 'EUR';
    case CZK = 'CZK';
    case BGN = 'BGN';

    /**
     * @throws UnknownCurrencyException
     */
    public function toCurrencyInstance(): CurrencyInstance
    {
        return CurrencyInstance::of($this->value);
    }
}
