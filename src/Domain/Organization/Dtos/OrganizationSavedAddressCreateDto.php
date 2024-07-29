<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Domain\Address\Dtos\AddressCreateDto;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationSavedAddressCreateDto extends Data
{
    public function __construct(
        public readonly bool $default,
        public readonly string $name,
        public readonly AddressCreateDto $address,
    ) {}

    /**
     * @param ValidationContext $context
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(ValidationContext $context): array
    {
        if (Str::contains(request()->url(), 'shipping-addresses')) {
            return [
                'address.name' => ['string', 'nullable', 'max:255', 'required_without:address.company_name'],
                'address.company_name' => ['string', 'nullable', 'max:255', 'required_without:address.name'],
            ];
        }

        return [];
    }
}
