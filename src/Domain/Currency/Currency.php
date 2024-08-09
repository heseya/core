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

    public const DEFAULT_PRICE_MAP_PLN = '019130e4-d59b-78fb-989a-f0d4431dapln';
    public const DEFAULT_PRICE_MAP_GBP = '019130e4-d59b-78fb-989a-f0d4431dagbp';
    public const DEFAULT_PRICE_MAP_EUR = '019130e4-d59b-78fb-989a-f0d4431daeur';
    public const DEFAULT_PRICE_MAP_CZK = '019130e4-d59b-78fb-989a-f0d4431daczk';
    public const DEFAULT_PRICE_MAP_BGN = '019130e4-d59b-78fb-989a-f0d4431dabgn';

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

    public function getDefaultPriceMapId(): string
    {
        return match ($this) {
            self::PLN => self::DEFAULT_PRICE_MAP_PLN,
            self::GBP => self::DEFAULT_PRICE_MAP_GBP,
            self::EUR => self::DEFAULT_PRICE_MAP_EUR,
            self::CZK => self::DEFAULT_PRICE_MAP_CZK,
            self::BGN => self::DEFAULT_PRICE_MAP_BGN,
        };
    }
}
