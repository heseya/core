<?php

namespace App\DTO;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class ReorderDto extends Data
{
    public function __construct(
        public array $ids,
    ) {
    }

    public static function rules(ValidationContext $context): array
    {
        return [
            'ids.*' => ['string', 'uuid'],
        ];
    }
}
