<?php

namespace App\Dtos;

use Heseya\Dto\Dto;

final class ProductPriceDto extends Dto
{
    public function __construct(
        public readonly string $id,
        public readonly float $price_min,
        public readonly float $price_max,
    ) {
    }
}
