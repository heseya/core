<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use Domain\ProductAttribute\Enums\AttributeType;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class AttributeUpdateDto extends Data
{
    public function __construct(
        #[Max(255)]
        public readonly Optional|string $name,
        #[Max(255), AlphaDash, Unique('attributes')]
        public readonly Optional|string $slug,
        public readonly AttributeType|Optional $type,
        public readonly bool|Optional $global,
        public readonly bool|Optional $sortable,
    ) {}
}
