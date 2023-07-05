<?php

namespace App\Dtos;

use Brick\Money\Money;
use Heseya\Dto\Dto;

final class ProductPriceDto extends Dto
{
    public function __construct(
        public readonly string $id,
        public readonly Money $price_min,
        public readonly Money $price_max,
    ) {
    }
}
