<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use App\Rules\ConsentsExists;
use App\Rules\OrganizationUniqueVat;
use App\Rules\RequiredConsents;
use Domain\Address\Dtos\AddressUpdateDto;
use Domain\Consent\Enums\ConsentType;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationUpdateDto extends Data
{
    /**
     * @param Optional|array<string, bool> $consents
     */
    public function __construct(
        #[Unique('organizations', 'client_id', ignore: new RouteParameterReference('organization.id'), ignoreColumn: 'id')]
        public readonly Optional|string|null $client_id,
        public readonly Optional|string $billing_email,
        #[Rule(new OrganizationUniqueVat())]
        public readonly AddressUpdateDto|Optional $billing_address,
        #[Uuid, Exists('sales_channels', 'id')]
        public readonly Optional|string|null $sales_channel_id,
        public readonly array|Optional $consents,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'consents' => [new RequiredConsents(ConsentType::ORGANIZATION)],
            'consents.*' => ['boolean', new ConsentsExists(ConsentType::ORGANIZATION)],
        ];
    }
}
