<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Domain\Address\Dtos\AddressCreateDto;
use Spatie\LaravelData\Data;

final class OrganizationSavedAddressCreateDto extends Data
{
    public function __construct(
        public readonly bool $default,
        public readonly string $name,
        public readonly AddressCreateDto $address,
    ) {}
}
