<?php

declare(strict_types=1);

namespace Domain\ProductSet\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class ProductSetProductsIndexDto extends Data
{
    public function __construct(
        public readonly bool|Optional $public,
    ) {}
}
