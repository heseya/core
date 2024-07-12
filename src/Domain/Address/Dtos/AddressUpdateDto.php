<?php

declare(strict_types=1);

namespace Domain\Address\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class AddressUpdateDto extends Data
{
    public function __construct(
        #[Max(255)]
        public readonly Optional|string $name,
        #[Max(255)]
        public readonly Optional|string $address,
        #[Max(15)]
        public readonly Optional|string|null $vat,
        #[Max(16)]
        public readonly Optional|string $zip,
        #[Max(255)]
        public readonly Optional|string $city,
        #[Max(2)]
        public readonly Optional|string $country,
        #[Max(20)]
        public readonly Optional|string $phone,
    ) {}
}
