<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use App\Rules\ConsentsExists;
use App\Rules\OrganizationUniqueVat;
use App\Rules\RequiredConsents;
use Domain\Address\Dtos\AddressCreateDto;
use Domain\Consent\Enums\ConsentType;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationCreateDto extends Data
{
    /**
     * @param DataCollection<int, OrganizationSavedAddressCreateDto> $shipping_addresses
     * @param array<string, bool> $consents
     */
    public function __construct(
        #[Unique('organizations', 'client_id')]
        public readonly Optional|string|null $client_id,
        public readonly string $billing_email,
        #[Rule(new OrganizationUniqueVat())]
        public readonly AddressCreateDto $billing_address,
        #[Uuid, Exists('sales_channels', 'id')]
        public readonly Optional|string|null $sales_channel_id,
        #[DataCollectionOf(OrganizationSavedAddressCreateDto::class), Min(1)]
        public readonly DataCollection $shipping_addresses,
        public readonly array $consents,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'consents' => ['present', 'array', new RequiredConsents(ConsentType::ORGANIZATION)],
            'consents.*' => ['boolean', new ConsentsExists(ConsentType::ORGANIZATION)],
        ];
    }
}
