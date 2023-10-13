<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Brick\Money\Currency;
use Brick\Money\Money;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\LaravelData\Casts\CurrencyCast;
use Support\LaravelData\Casts\MoneyCast;

final class ProductSearchPriceDto extends Data
{
    public function __construct(
        #[WithCast(MoneyCast::class)]
        public Money|Optional $min,
        #[WithCast(MoneyCast::class)]
        public Money|Optional $max,
        #[WithCast(CurrencyCast::class)]
        public Currency|Optional $currency,
    ) {}
}