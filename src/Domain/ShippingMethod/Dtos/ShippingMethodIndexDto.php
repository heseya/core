<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Dtos;

use App\Rules\Price;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
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
     * @param array<string> $product_ids
     * @param array<string> $product_set_ids
     * @param bool $is_product_blocklist
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
        #[ArrayType]
        public array $product_ids = [],
        #[ArrayType]
        public array $product_set_ids = [],
        #[BooleanType]
        public bool $is_product_blocklist = true,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'ids.*' => ['uuid', 'exists:shipping_methods,id'],
            'cart_value' => [new Price(['value'])],
            'product_ids.*' => ['uuid', 'exists:products,id'],
            'product_set_ids.*' => ['uuid', 'exists:product_sets,id'],
        ];
    }
}
