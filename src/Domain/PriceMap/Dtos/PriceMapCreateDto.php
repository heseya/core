<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class PriceMapCreateDto extends Data
{
    public function __construct(
        public string $name,
        public Optional|string|null $description,
        public string $currency,
        public bool $is_net,
    ) {}
}
