<?php

declare(strict_types=1);

namespace Domain\Price\Dtos;

use App\Models\Price;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelService;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Support\LaravelData\Casts\MoneyCast;
use Support\LaravelData\Transformers\MoneyToAmountTransformer;

final class ProductCachedPriceDto extends Data
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
        #[Exists('sales_channels', 'id')]
        public string $sales_channel_id,
    ) {}

    public static function fromPrice(Price $price): self
    {
        return self::from([
            'net' => $price->net,
            'gross' => $price->gross,
            'currency' => $price->currency,
            'sales_channel_id' => $price->sales_channel_id,
        ]);
    }

    public static function fromPriceMapProductPriceAndSalesChannel(PriceMapProductPrice $price, SalesChannel $salesChannel): self
    {
        if ($price->is_net) {
            $net = $price->value;
            $gross = app(SalesChannelService::class)->addVat($price->value, app(SalesChannelService::class)->getVatRate($salesChannel));
        } else {
            $net = app(SalesChannelService::class)->removeVat($price->value, app(SalesChannelService::class)->getVatRate($salesChannel));
            $gross = $price->value;
        }

        return self::from([
            'net' => $net,
            'gross' => $gross,
            'currency' => $price->currency,
            'sales_channel_id' => $salesChannel->id,
        ]);
    }
}
