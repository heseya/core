<?php

namespace App\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ProductReorderDto extends Data
{
    public function __construct(
        #[Required, Uuid, Exists('products', 'id')]
        public string $id,
        #[Required, IntegerType]
        public int $order,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'order' => ['gte:0'],
        ];
    }
}
