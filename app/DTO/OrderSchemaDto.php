<?php

namespace App\DTO;

use Domain\Price\Dtos\PriceDto;
use Spatie\LaravelData\Data;

class OrderSchemaDto extends Data {

    public function __construct(
        public readonly string $name,
        public readonly PriceDto $price,
        public readonly PriceDto $price_initial,
        public readonly string $value,
    ) {}

}

