<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use Domain\Currency\Currency;
use Domain\Order\Dtos\OrderPriceDto;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Support\Dtos\DataWithGlobalMetadata;

final class CartItemResource extends DataWithGlobalMetadata
{
    public function __construct(
        public string $cartitem_id,
        public OrderPriceDto $price,
        public OrderPriceDto $price_discounted,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        public float $quantity,
    ) {}
}
