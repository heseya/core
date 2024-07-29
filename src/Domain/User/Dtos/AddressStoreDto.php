<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Rules\FullName;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AddressStoreDto extends Data
{
    public function __construct(
        public Optional|string|null $name,
        public Optional|string|null $company_name,
        #[Required, StringType, Max(255)]
        public string $address,
        #[Required, StringType, Max(20)]
        public string $phone,
        #[Required, StringType, Max(16)]
        public string $zip,
        #[Required, StringType, Max(255)]
        public string $city,
        #[Required, StringType, Max(2)]
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
                'name' => ['required_without:company_name', 'string', 'max:255'],
                'company_name' => ['string', 'nullable', 'max:255', 'required_without:name'],
            ];
        }

        return [
            'name' => ['required_without:company_name', 'string', 'max:255', new FullName()],
            'company_name' => ['string', 'nullable', 'max:255', 'required_without:name'],
        ];
    }
}
