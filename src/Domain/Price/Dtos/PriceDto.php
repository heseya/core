<?php

declare(strict_types=1);

namespace Domain\Price\Dtos;

use App\Models\Price;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Support\LaravelData\Casts\MoneyCast;
use Support\LaravelData\Transformers\MoneyToAmountTransformer;

final class PriceDto extends Data
{
    public function __construct(
        #[WithTransformer(MoneyToAmountTransformer::class)]
        #[WithCast(MoneyCast::class)]
        public Money $value,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        public bool $is_net = true,
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
}
