<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Dtos;

use Domain\User\Dtos\AddressStoreDto;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ManufacturerCreateDto extends Data
{
    /**
     * @param string[] $product_ids
     */
    public function __construct(
        public readonly Optional|string|null $name,
        public readonly Optional|string|null $first_name,
        public readonly Optional|string|null $last_name,
        #[Email, Max(255)]
        public readonly string $email,
        public readonly AddressStoreDto $address,
        public readonly array|Optional $product_ids,
    ) {}

    /**
     * @param ValidationContext $context
     *
     * @return array<string, string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required_without:first_name,last_name'],
            'first_name' => ['required_without:name'],
            'last_name' => ['required_without:name'],
        ];
    }
}
