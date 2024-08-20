<?php

declare(strict_types=1);

namespace Domain\Currency;

use App\Enums\Traits\EnumTrait;
use Brick\Money\Currency as CurrencyInstance;
use Brick\Money\Exception\UnknownCurrencyException;
use Illuminate\Support\Arr;

enum Currency: string
{
    use EnumTrait;

    public const DEFAULT = self::PLN;

    /** ISO 4217 Numeric Code */
    public const DEFAULT_PRICE_MAP_PLN = '00000000-0000-0000-0000-000000000985';
    public const DEFAULT_PRICE_MAP_EUR = '00000000-0000-0000-0000-000000000978';
    public const DEFAULT_PRICE_MAP_GBP = '00000000-0000-0000-0000-000000000826';
    public const DEFAULT_PRICE_MAP_CZK = '00000000-0000-0000-0000-000000000203';
    public const DEFAULT_PRICE_MAP_BGN = '00000000-0000-0000-0000-000000000975';

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
            self::BGN => self::DEFAULT_PRICE_MAP_BGN,
            self::CZK => self::DEFAULT_PRICE_MAP_CZK,
            self::EUR => self::DEFAULT_PRICE_MAP_EUR,
            self::GBP => self::DEFAULT_PRICE_MAP_GBP,
            self::PLN => self::DEFAULT_PRICE_MAP_PLN,
        };
    }

    /**
     * @return array<int,string>
     */
    public static function defaultPriceMapIds(): array
    {
        return Arr::map(self::cases(), fn (Currency $currency) => $currency->getDefaultPriceMapId());
    }
}
