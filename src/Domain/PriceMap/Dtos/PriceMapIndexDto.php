<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class PriceMapIndexDto extends Data
{
    public function __construct(
        public Optional|string $search,
    ) {}
}
