<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class AttributeIndexDto extends Data
{
    /**
     * @param string[]|Optional $ids
     * @param string[]|Optional $metadata
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        #[Max(255)]
        public readonly Optional|string $search,
        public readonly bool|Optional $global,
        public readonly array|Optional $ids,
        public readonly array|Optional $metadata,
        public readonly array|Optional $metadata_private,
        public readonly bool|Optional $sortable,
    ) {}
}
