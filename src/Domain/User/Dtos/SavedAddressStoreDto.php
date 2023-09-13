<?php

namespace Domain\User\Dtos;

use App\Enums\SavedAddressType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class SavedAddressStoreDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
        #[Required]
        public bool $default,
        #[WithCast(EnumCast::class, SavedAddressType::class)]
        #[Required]
        public SavedAddressType $type,
        public AddressDto $address,
    ) {
    }
}
