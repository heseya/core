<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Domain\Currency\Currency;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class PriceMapUpdateDto extends Data
{
    public function __construct(
        #[Sometimes()]
        public Optional|string $name,
        #[Sometimes()]
        public Optional|string|null $description,
        #[Sometimes(), Enum(Currency::class)]
        public Optional|string $currency,
        #[Sometimes()]
        public bool|Optional $is_net,
    ) {}
}
