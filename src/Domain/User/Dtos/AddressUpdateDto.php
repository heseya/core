<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Rules\FullName;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AddressUpdateDto extends Data
{
    public function __construct(
        public Optional|string|null $name,
        public Optional|string|null $company_name,
        #[StringType, Max(255)]
        public string $address,
        #[StringType, Max(20)]
        public string $phone,
        #[StringType, Max(16)]
        public string $zip,
        #[StringType, Max(255)]
        public string $city,
        #[StringType, Max(2)]
        public string $country,
        #[Nullable, StringType, Max(15)]
        public Optional|string|null $vat,
    ) {}

    /**
     * @return array<string,array<int,mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        if (Str::contains(request()->url(), 'billing-addresses')) {
            return [
                'name' => ['string', 'max:255', 'required_without:company_name'],
                'company_name' => ['string', 'max:255', 'required_without:name'],
            ];
        }

        return [
            'name' => ['string', 'max:255', new FullName()],
            'company_name' => ['string', 'max:255', 'required_without:name'],
        ];
    }
}
