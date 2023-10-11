<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationUpdateDto extends Data
{
    /**
     * @param array<string, string>|Optional $address
     */
    public function __construct(
        public readonly Optional|string $name,
        public readonly Optional|string $description,
        public readonly Optional|string $phone,
        public readonly Optional|string $email,
        public readonly array|Optional $address,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'address.name' => ['string', 'max:255'],
            'address.phone' => ['string', 'max:20'],
            'address.address' => ['string', 'max:255'],
            'address.zip' => ['string', 'max:16'],
            'address.city' => ['string', 'max:255'],
            'address.country' => ['string', 'size:2'],
            'address.vat' => ['nullable', 'string', 'max:15'],
        ];
    }
}
