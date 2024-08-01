<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use App\Rules\ConsentsExists;
use App\Rules\MyOrganizationUniqueVat;
use App\Rules\RequiredConsents;
use Domain\Address\Dtos\AddressUpdateDto;
use Domain\Consent\Enums\ConsentType;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationPublicUpdateDto extends Data
{
    /**
     * @param Optional|array<string, bool> $consents
     */
    public function __construct(
        public readonly Optional|string $billing_email,
        #[Rule(new MyOrganizationUniqueVat())]
        public readonly AddressUpdateDto|Optional $billing_address,
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
