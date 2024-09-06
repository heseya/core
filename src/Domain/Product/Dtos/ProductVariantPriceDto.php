<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class ProductVariantPriceDto extends Data
{
    /**
     * @param array<string,string>|Optional|null $schemas
     */
    public function __construct(
        #[Exists('products', 'id')]
        public string $product_id,
        public array|Optional|null $schemas,
    ) {}
}
