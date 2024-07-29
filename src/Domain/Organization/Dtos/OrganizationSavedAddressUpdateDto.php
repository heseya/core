<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use App\Rules\OrganizationSavedAddressDefault;
use Domain\Address\Dtos\AddressUpdateDto;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationSavedAddressUpdateDto extends Data
{
    public function __construct(
        #[Rule(new OrganizationSavedAddressDefault())]
        public readonly bool|Optional $default,
        public readonly Optional|string $name,
        public readonly AddressUpdateDto|Optional $address,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(ValidationContext $context): array
    {
        if (Str::contains(request()->url(), 'shipping-addresses')) {
            return [
                'address.name' => ['string', 'sometimes', 'nullable', 'max:255', 'required_without:address.company_name'],
                'address.company_name' => ['string', 'sometimes', 'nullable', 'max:255', 'required_without:address.name'],
            ];
        }

        return [];
    }
}
