<?php

declare(strict_types=1);

namespace Domain\Currency;

use App\Enums\Traits\EnumTrait;
use Brick\Money\Currency as CurrencyInstance;

enum Currency: string
{
    use EnumTrait;

    public const DEFAULT = self::PLN;

    case PLN = 'PLN';
    case EUR = 'EUR';

    public function toCurrencyInstance(): CurrencyInstance
    {
        return CurrencyInstance::of($this->value);
    }
}
