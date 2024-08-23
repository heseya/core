<?php

declare(strict_types=1);

namespace Domain\Price\Dtos;

use App\Models\Price;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Support\LaravelData\Casts\MoneyCast;

final class PriceDto extends Data
{
    public function __construct(
        #[WithCast(MoneyCast::class)]
        public Money $value,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        public bool $is_net = false,
    ) {}

    public static function fromModel(Price $price): self
    {
        return self::from([
            'value' => $price->value,
            'currency' => $price->currency,
            'is_net' => $price->is_net,
        ]);
    }

    public static function fromMoney(Money $money): self
    {
        $currency = Currency::from($money->getCurrency()->getCurrencyCode());

        return new self($money, $currency);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value->getMinorAmount(),
            'currency' => $this->currency->value,
            'is_net' => $this->is_net,
        ];
    }
}
