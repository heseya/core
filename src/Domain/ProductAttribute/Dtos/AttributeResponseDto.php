<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use Domain\ProductAttribute\Enums\AttributeType;
use Spatie\LaravelData\Data;

final class AttributeResponseDto extends Data
{
    public float|int|string|null $min;
    public float|int|string|null $max;

    /** @var string[] */
    public array $metadata;
    /** @var string[] */
    public array $metadata_private;

    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $description,
        public readonly AttributeType $type,
        public readonly bool $global,
        public readonly bool $sortable,
    ) {}
}
