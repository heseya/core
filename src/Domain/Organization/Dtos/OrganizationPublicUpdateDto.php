<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use App\Rules\MyOrganizationUniqueVat;
use Domain\Address\Dtos\AddressUpdateDto;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class OrganizationPublicUpdateDto extends Data
{
    public function __construct(
        public readonly Optional|string $billing_email,
        #[Rule(new MyOrganizationUniqueVat())]
        public readonly AddressUpdateDto|Optional $billing_address,
    ) {}
}
