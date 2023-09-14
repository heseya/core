<?php

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class AddressStoreDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
        #[Required, StringType, Max(255)]
        public string $address,
        #[Required, StringType, Max(20)]
        public string $phone,
        #[Required, StringType, Max(16)]
        public string $zip,
        #[Required, StringType, Max(255)]
        public string $city,
        #[Required, StringType, Max(2)]
        public string $country,
        #[Nullable, StringType, Max(15)]
        public string|Optional $vat,
    ) {
    }
}
