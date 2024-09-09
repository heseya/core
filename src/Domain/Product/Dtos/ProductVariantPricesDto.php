<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class ProductVariantPricesDto extends Data
{
    /**
     * @param ProductVariantPriceDtoCollection<int,ProductVariantPriceDto> $products
     */
    public function __construct(
        #[DataCollectionOf(ProductVariantPriceDto::class)]
        public ProductVariantPriceDtoCollection $products,
    ) {}
}
