<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Uuid;
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
        #[Uuid, Exists('sales_channels', 'id')]
        public readonly Optional|string $sales_channel_id,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
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
            'sales_channel_id' => [Rule::prohibitedIf(fn () => !Auth::user()?->hasPermissionTo('organizations.verify'))],
        ];
    }
}
