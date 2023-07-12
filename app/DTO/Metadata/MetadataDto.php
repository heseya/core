<?php

declare(strict_types=1);

namespace App\DTO\Metadata;

use Spatie\LaravelData\Data;

final class MetadataDto extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly bool|float|int|string|null $value,
        public readonly bool $public,
        public readonly string $value_type,
    ) {}
}
