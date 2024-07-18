<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Domain\Address\Dtos\AddressUpdateDto;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class OrganizationSavedAddressUpdateDto extends Data
{
    public function __construct(
        #[Uuid]
        public readonly string $id,
        public readonly bool $default,
        public readonly string $name,
        public readonly AddressUpdateDto $address,
    ) {}
}
