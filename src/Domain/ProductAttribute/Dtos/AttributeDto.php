<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use Domain\ProductAttribute\Enums\AttributeType;
use Spatie\LaravelData\Data;

final class AttributeDto extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $description,
        public readonly float|null $min_number,
        public readonly float|null $max_number,
        public readonly string|null $min_date,
        public readonly string|null $max_date,
        public readonly AttributeType $type,
        public readonly bool $global,
        public readonly bool $sortable,
    ) {}
}
