<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Dtos;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ShippingMethodReorderDto extends Data
{
    /**
     * @param array<string> $shipping_methods
     */
    public function __construct(
        #[Required, ArrayType]
        public array $shipping_methods,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'shipping_methods.*' => ['uuid', 'exists:shipping_methods,id'],
        ];
    }
}
