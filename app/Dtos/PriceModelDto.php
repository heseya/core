<?php

namespace App\Dtos;

use Brick\Money\Money;
use Heseya\Dto\Dto;

class PriceModelDto extends Dto
{
    public function __construct(
        public readonly Money $value,
        public readonly string $model_id,
        public readonly string $model_type,
        public readonly string $price_type,
        public readonly bool $is_net,
    ) {}
}
