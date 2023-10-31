<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class AddressUpdateDto extends Data
{
    public function __construct(
        #[StringType, Max(255)]
        public string $name,
        #[StringType, Max(255)]
        public string $address,
        #[StringType, Max(20)]
        public string $phone,
        #[StringType, Max(16)]
        public string $zip,
        #[StringType, Max(255)]
        public string $city,
        #[StringType, Max(2)]
        public string $country,
        #[Nullable, StringType, Max(15)]
        public Optional|string|null $vat,
    ) {}
}
