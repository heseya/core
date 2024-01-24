<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Rules\FullName;
use App\Rules\StreetNumber;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class AddressStoreDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255), Rule(new FullName())]
        public string $name,
        #[Required, StringType, Max(255), Rule(new StreetNumber())]
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
        public Optional|string|null $vat,
    ) {}
}
