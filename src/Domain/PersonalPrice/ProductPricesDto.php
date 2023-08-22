<?php

declare(strict_types=1);

namespace Domain\PersonalPrice;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ProductPricesDto extends Data
{
    /**
     * @param string[] $ids
     */
    public function __construct(
        public readonly array $ids,
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'ids.*' => ['uuid'],
        ];
    }
}
