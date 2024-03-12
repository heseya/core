<?php

declare(strict_types=1);

namespace Domain\Wishlist\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class WishlistCheckDto extends Data
{
    /**
     * @param array<int, string> $product_ids
     */
    public function __construct(
        public readonly array $product_ids,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'product_ids.*' => ['uuid', 'exists:products,id'],
        ];
    }
}
