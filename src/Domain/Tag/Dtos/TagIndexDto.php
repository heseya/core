<?php

declare(strict_types=1);

namespace Domain\Tag\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class TagIndexDto extends Data
{
    /**
     * @param string[]|Optional $ids
     */
    public function __construct(
        public readonly Optional|string|null $search,
        public readonly array|Optional $ids,
    ) {}

    /**
     * @return string[][]
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'ids.*' => ['uuid'],
        ];
    }
}
