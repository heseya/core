<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Enums\SavedAddressType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

final class SavedAddressStoreDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
        #[Required, BooleanType]
        public bool $default,
        #[WithCast(EnumCast::class, SavedAddressType::class)]
        #[Required]
        public SavedAddressType $type,
        public AddressStoreDto $address,
    ) {}
}
