<?php

declare(strict_types=1);

namespace Domain\Page\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class PageIndexDto extends Data
{
    /**
     * @param string[]|Optional $ids
     * @param string[]|Optional $metadata
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        public readonly Optional|string $search,
        public readonly array|Optional $ids,
        public readonly array|Optional $metadata,
        public readonly array|Optional $metadata_private,
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
