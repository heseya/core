<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use App\Rules\OrganizationSavedAddressDefault;
use Domain\Address\Dtos\AddressUpdateDto;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class OrganizationSavedAddressUpdateDto extends Data
{
    public function __construct(
        #[Rule(new OrganizationSavedAddressDefault())]
        public readonly bool|Optional $default,
        public readonly Optional|string $name,
        public readonly AddressUpdateDto|Optional $address,
    ) {}
}
