<?php

declare(strict_types=1);

namespace Domain\Order\Dtos;

use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelService;
use InvalidArgumentException;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Support\LaravelData\Casts\MoneyCast;
use Support\LaravelData\Transformers\MoneyToAmountTransformer;

final class OrderPriceDto extends Data
{
    public function __construct(
        #[WithTransformer(MoneyToAmountTransformer::class)]
        #[WithCast(MoneyCast::class)]
        public Money $net,
        #[WithTransformer(MoneyToAmountTransformer::class)]
        #[WithCast(MoneyCast::class)]
        public Money $gross,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        public BigDecimal $vat_rate,
    ) {}

    public static function fromMoneyAndSalesChannel(Money $price, SalesChannel $salesChannel, bool $is_net = true): self
    {
        if ($salesChannel->priceMap === null) {
            throw new InvalidArgumentException();
        }

        return self::fromMoneyAndVatRate($price, app(SalesChannelService::class)->getVatRate($salesChannel), $is_net);
    }

    public static function fromMoneyAndVatRate(Money $price, BigDecimal|float|string $vat_rate, bool $is_net = true): self
    {
        if (!$vat_rate instanceof BigDecimal) {
            $vat_rate = BigDecimal::of($vat_rate)->multipliedBy(0.01)->abs();
        }

        if ($is_net) {
            $net = $price;
            $gross = app(SalesChannelService::class)->addVat($price, $vat_rate);
        } else {
            $net = app(SalesChannelService::class)->removeVat($price, $vat_rate);
            $gross = $price;
        }

        return self::from([
            'net' => $net,
            'gross' => $gross,
            'currency' => $price->getCurrency()->getCurrencyCode(),
            'vat_rate' => $vat_rate,
        ]);
    }
}
