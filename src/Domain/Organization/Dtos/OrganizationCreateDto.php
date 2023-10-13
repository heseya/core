<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationCreateDto extends Data
{
    /**
     * @param array<string, string> $address
     */
    public function __construct(
        public readonly string $name,
        public readonly Optional|string $description,
        public readonly string $phone,
        public readonly string $email,
        public readonly array $address,
        #[Uuid, Exists('sales_channels', 'id')]
        public readonly Optional|string $sales_channel_id,
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
