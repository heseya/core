<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Dtos;

use App\Rules\Price;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ShippingMethodIndexDto extends Data
{
    /**
     * @param Optional|string $country
     * @param array<string>|Optional $ids
     * @param array<string, string>|Optional $cart_value
     * @param array<string, string>|Optional $metadata
     * @param array<string, string>|Optional $metadata_private
     * @param array<string> $items
     * @param string|Optional $sales_channel_id
     */
    public function __construct(
        #[StringType, Size(2), Exists('countries', 'code')]
        public Optional|string $country,
        #[ArrayType, Exists('shipping_methods', 'id')]
        public array|Optional $ids,
        #[ArrayType]
        public array|Optional $cart_value,
        #[ArrayType]
        public array|Optional $metadata,
        #[ArrayType]
        public array|Optional $metadata_private,
        #[StringType, Exists('sales_channels', 'id')]
        public Optional|string $sales_channel_id,
        #[ArrayType]
        public array $items = [],
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'ids.*' => ['uuid', 'exists:shipping_methods,id'],
            'cart_value' => [new Price(['value'])],
            'items.*' => ['uuid', 'exists:products,id'],
        ];
    }
}
