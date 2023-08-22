<?php

namespace App\Dtos;

use Domain\Price\Dtos\PriceDto;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\LaravelData\Data;

final class ProductPriceDto extends Data
{
    /**
     * @param AnonymousResourceCollection<int, PriceDto> $prices_min
     * @param AnonymousResourceCollection<int, PriceDto> $prices_max
     */
    public function __construct(
        public readonly string $id,
        public readonly AnonymousResourceCollection $prices_min,
        public readonly AnonymousResourceCollection $prices_max,
    ) {}
}
