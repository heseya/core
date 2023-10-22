<?php

namespace App\DTO;

use Domain\Price\Dtos\PriceDto;
use Spatie\LaravelData\Data;

class OrderDiscountDto extends Data {

    /**
    * @param OrderSchemaDto[] $schemas
    * @param OrderDiscountDto[] $discounts
    **/
    public function __construct(
        public readonly string $cartitem_id,
        public readonly string $product_id,
        public readonly string $name,
        public readonly PriceDto $price,
        public readonly PriceDto $price_initial,
        public readonly PriceDto $price_base,
        public readonly PriceDto $price_base_initial,
        public readonly float $quantity,
        public readonly array $schemas,
        public readonly array $discounts,
    ) {}

}

