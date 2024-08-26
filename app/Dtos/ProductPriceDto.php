<?php

namespace App\Dtos;

use App\Http\Resources\PriceResource;
use Spatie\LaravelData\Data;

final class ProductPriceDto extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly PriceResource $price_min,
    ) {}
}
