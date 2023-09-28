<?php

declare(strict_types=1);

namespace Domain\Price\Dtos;

use App\Models\Price;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\SalesChannel\Models\SalesChannel;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\LaravelData\Casts\MoneyCast;

final class PriceDto extends Data
{
    public function __construct(
        #[WithCast(MoneyCast::class)]
        public Money $value,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        public Optional|string|null $sales_channel_id = null,
    ) {}

    public static function fromModel(Price $price): self
    {
        return self::from([
            'value' => $price->value,
            'currency' => $price->currency,
            'sales_channel_id' => $price->sales_channel_id,
        ]);
    }

    public static function fromMoney(Money $money): self
    {
        return new self($money, Currency::from($money->getCurrency()->getCurrencyCode()));
    }

    public function withSalesChannel(Optional|SalesChannel|string|null $salesChannel): self
    {
        $this->sales_channel_id = $salesChannel instanceof SalesChannel ? $salesChannel->getKey() : $salesChannel;

        return $this;
    }
}
