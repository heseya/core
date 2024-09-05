<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class ProductVariantPriceDto extends Data
{
    /**
     * @param array<string,string>|Optional $schemas
     */
    public function __construct(
        public array|Optional $schemas,
    ) {}
}
