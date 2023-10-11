<?php

namespace App\Dtos;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Symfony\Contracts\Service\Attribute\Required;

final class ProductsReorderDto extends Data
{
    public function __construct(
        #[DataCollectionOf(ProductReorderDto::class), Required, Size(1)]
        public DataCollection $products,
    ) {}
}
