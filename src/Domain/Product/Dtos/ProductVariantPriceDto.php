<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Spatie\LaravelData\Data;

final class ProductVariantPriceDto extends Data
{
    /**
     * @param array<string,string> $schemas
     */
    public function __construct(
        public array $schemas,
    ) {}
}
