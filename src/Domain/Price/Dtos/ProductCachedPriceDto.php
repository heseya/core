<?php

declare(strict_types=1);

namespace Domain\Price\Dtos;

use App\Models\Price;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Support\LaravelData\Casts\MoneyCast;

final class ProductCachedPriceDto extends Data
{
    public function __construct(
        #[WithCast(MoneyCast::class)]
        public Money $net,
        #[WithCast(MoneyCast::class)]
        public Money $gross,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        #[Exists('sales_channels', 'id')]
        public string $sales_channel_id,
    ) {}

    public static function fromModel(Price $price): self
    {
        return self::from([
            'net' => $price->net,
            'gross' => $price->gross,
            'currency' => $price->currency,
            'sales_channel_id' => $price->sales_channel_id,
        ]);
    }
}
