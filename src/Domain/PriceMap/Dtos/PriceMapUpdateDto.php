<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class PriceMapUpdateDto extends Data
{
    public function __construct(
        public Optional|string $name,
        public Optional|string|null $description,
        public Optional|string $currency,
        public bool|Optional $is_net,
    ) {}
}
