<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use App\Rules\OrganizationUniqueVat;
use Domain\Address\Dtos\AddressUpdateDto;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

final class OrganizationUpdateDto extends Data
{
    public function __construct(
        #[Unique('organizations', 'client_id', ignore: new RouteParameterReference('organization.id'), ignoreColumn: 'id')]
        public readonly Optional|string|null $client_id,
        public readonly Optional|string $billing_email,
        #[Rule(new OrganizationUniqueVat())]
        public readonly AddressUpdateDto|Optional $billing_address,
        #[Uuid, Exists('sales_channels', 'id')]
        public readonly Optional|string|null $sales_channel_id,
    ) {}
}