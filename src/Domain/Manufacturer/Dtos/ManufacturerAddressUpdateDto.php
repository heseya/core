<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ManufacturerAddressUpdateDto extends Data
{
    public function __construct(
        public string $name,
        #[StringType, Max(255)]
        public string $address,
        #[StringType, Max(20)]
        public Optional|string $phone,
        #[StringType, Max(16)]
        public string $zip,
        #[StringType, Max(255)]
        public string $city,
        #[StringType, Max(2)]
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
            'name' => ['string', 'max:255'],
        ];
    }
}
