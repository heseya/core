<?php

declare(strict_types=1);

namespace Domain\PaymentMethods\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class PaymentMethodIndexDto extends Data
{
    /**
     * @param Optional|string $shipping_method_id
     * @param Optional|string $order_code
     * @param Optional|array<int, string> $ids
     */
    public function __construct(
        #[Uuid, Exists('shipping_methods', 'id')]
        public readonly Optional|string $shipping_method_id,
        #[Exists('orders', 'code')]
        public readonly Optional|string $order_code,
        public readonly array|Optional $ids,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'ids.*' => ['uuid'],
        ];
    }
}
