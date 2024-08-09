<?php

declare(strict_types=1);

namespace Domain\PriceMap\Dtos;

use Domain\Currency\Currency;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class PriceMapCreateDto extends Data
{
    public function __construct(
        #[Required()]
        public string $name,
        #[Sometimes()]
        public Optional|string|null $description,
        #[Required(), Enum(Currency::class)]
        public string $currency,
        #[Required()]
        public bool $is_net,
    ) {}
}
