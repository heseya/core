<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Dtos;

use Domain\User\Dtos\AddressUpdateDto;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class ManufacturerUpdateDto extends Data
{
    /**
     * @param string[] $product_ids
     */
    public function __construct(
        #[RequiredWithout(['first_name', 'last_name'])]
        public readonly Optional|string|null $name,
        #[RequiredWithout('name')]
        public readonly Optional|string|null $first_name,
        #[RequiredWithout('name')]
        public readonly Optional|string|null $last_name,
        #[Email, Max(255)]
        public readonly Optional|string $email,
        public readonly AddressUpdateDto|Optional $address,
        public readonly array|Optional $product_ids,
    ) {}
}
