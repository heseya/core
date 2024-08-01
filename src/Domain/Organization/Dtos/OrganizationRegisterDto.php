<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use App\Rules\ConsentsExists;
use App\Rules\EmailUnique;
use App\Rules\OrganizationUniqueVat;
use App\Rules\RequiredConsents;
use Domain\Address\Dtos\AddressCreateDto;
use Domain\Consent\Enums\ConsentType;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationRegisterDto extends Data
{
    /**
     * @param DataCollection<int, OrganizationSavedAddressCreateDto> $shipping_addresses
     * @param array<string, bool> $consents
     */
    public function __construct(
        #[Email, Max(255)]
        public readonly string $billing_email,
        #[Rule(new OrganizationUniqueVat())]
        public readonly AddressCreateDto $billing_address,
        #[DataCollectionOf(OrganizationSavedAddressCreateDto::class), Min(1)]
        public readonly DataCollection $shipping_addresses,
        #[Email, Max(255), Rule(new EmailUnique())]
        public readonly string $creator_email,
        public readonly string $creator_password,
        public readonly string $creator_name,
        public readonly Optional|string $captcha_token,
        public readonly array $consents,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'creator_password' => ['required', 'string', Password::defaults()],
            'billing_address.name' => ['string', 'nullable', 'max:255', 'required_without:billing_address.company_name'],
            'billing_address.company_name' => ['string', 'nullable', 'max:255', 'required_without:billing_address.name'],
            'shipping_addresses.*.address.name' => ['string', 'nullable', 'max:255', 'required_without:shipping_addresses.*.address.company_name'],
            'shipping_addresses.*.address.company_name' => ['string', 'nullable', 'max:255', 'required_without:shipping_addresses.*.address.name'],
            'consents' => ['present', 'array', new RequiredConsents(ConsentType::ORGANIZATION)],
            'consents.*' => ['boolean', new ConsentsExists(ConsentType::ORGANIZATION)],
        ];
    }
}
