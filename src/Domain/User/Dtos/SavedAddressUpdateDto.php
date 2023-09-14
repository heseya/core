<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Enums\SavedAddressType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class SavedAddressUpdateDto extends Data
{
    public function __construct(
        #[StringType, Nullable, Max(255)]
        public string $name,
        #[BooleanType]
        public bool $default,
        #[WithCast(EnumCast::class, SavedAddressType::class)]
        public Optional|SavedAddressType $type,
        public AddressUpdateDto|Optional $address,
    ) {}
}
