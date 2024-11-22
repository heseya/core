<?php

namespace Domain\Manufacturer\Dtos;

use Domain\User\Dtos\AddressStoreDto;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class ManufacturerCreateDto extends Data
{
    /**
     * @param string[] $product_ids
     */
    public function __construct(
        public readonly string|null|Optional $name,
        public readonly string|null|Optional $first_name,
        public readonly string|null|Optional $last_name,
        #[Email, Max(255)]
        public readonly string $email,
        public readonly AddressStoreDto $address,
        public readonly array|Optional $product_ids,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required_without:first_name,last_name'],
            'first_name' => ['required_without:name'],
            'last_name' => ['required_without:name'],
        ];
    }
}
