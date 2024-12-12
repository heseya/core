<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ManufacturerAddressStoreDto extends Data
{
    public function __construct(
        public string $name,
        #[Required, StringType, Max(255)]
        public string $address,
        #[Nullable, StringType, Max(20)]
        public Optional|string $phone,
        #[Required, StringType, Max(16)]
        public string $zip,
        #[Required, StringType, Max(255)]
        public string $city,
        #[Required, StringType, Max(2)]
        public string $country,
        #[Nullable, StringType, Max(15)]
        public Optional|string|null $vat,
    ) {}

    /**
     * @return array<string,array<int,mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
