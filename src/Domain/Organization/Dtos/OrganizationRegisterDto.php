<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use App\Rules\EmailUnique;
use App\Rules\OrganizationUniqueVat;
use Domain\Address\Dtos\AddressCreateDto;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationRegisterDto extends Data
{
    /**
     * @param DataCollection<int, OrganizationSavedAddressCreateDto> $shipping_addresses
     */
    public function __construct(
        #[Unique('organizations', 'client_id')]
        public readonly string $billing_email,
        #[Rule(new OrganizationUniqueVat())]
        public readonly AddressCreateDto $billing_address,
        #[DataCollectionOf(OrganizationSavedAddressCreateDto::class), Min(1)]
        public readonly DataCollection $shipping_addresses,
        public readonly string $creator_email,
        public readonly string $creator_password,
        public readonly string $creator_name,
        public readonly Optional|string $captcha_token,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'creator_email' => [
                'required',
                'email',
                'max:255',
                new EmailUnique(),
            ],
            'creator_password' => ['required', 'string', Password::defaults()],
        ];
    }
}
