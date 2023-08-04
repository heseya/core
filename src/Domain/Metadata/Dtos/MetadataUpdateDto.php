<?php

declare(strict_types=1);

namespace Domain\Metadata\Dtos;

use App\Enums\MetadataType;
use Spatie\LaravelData\Data;
use Support\LaravelData\ExtendedData;

final class MetadataUpdateDto extends Data
{
    use ExtendedData;

    public function __construct(
        public readonly string $name,
        public readonly bool|float|int|string|null $value,
        public readonly bool $public,
        public readonly MetadataType $value_type,
    ) {}
}
