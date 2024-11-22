<?php

namespace Domain\Manufacturer\Dtos;

use Domain\User\Dtos\AddressUpdateDto;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ManufacturerUpdateDto extends Data
{
    /**
     * @param string[] $product_ids
     */
    public function __construct(
        #[RequiredWithout(['first_name', 'last_name'])]
        public readonly string|null|Optional $name,
        #[RequiredWithout('name')]
        public readonly string|null|Optional $first_name,
        #[RequiredWithout('name')]
        public readonly string|null|Optional $last_name,
        #[Email, Max(255)]
        public readonly string|Optional $email,
        public readonly AddressUpdateDto|Optional $address,
        public readonly array|Optional $product_ids,
    ) {}
}
