<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use Brick\Money\Money;
use Domain\Currency\Currency;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\EnumCast;
use Support\Dtos\DataWithGlobalMetadata;
use Support\LaravelData\Casts\MoneyCast;
use Support\LaravelData\Transformers\MoneyToAmountTransformer;

final class CouponShortResource extends DataWithGlobalMetadata
{
    public function __construct(
        public string $id,
        public string $name,
        #[WithTransformer(MoneyToAmountTransformer::class)]
        #[WithCast(MoneyCast::class)]
        public Money $value,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        public string $code,
    ) {}
}
