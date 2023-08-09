<?php

declare(strict_types=1);

namespace Domain\Metadata\Dtos;

use Domain\Metadata\Enums\MetadataType;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Support\LaravelData\ExtendedData;

final class MetadataUpdateDto extends Data
{
    use ExtendedData;

    public function __construct(
        #[Max(255), AlphaDash]
        public readonly string $name,
        public readonly bool|float|int|string|null $value,
        public readonly bool $public,
        public readonly MetadataType $value_type,
    ) {}
}
