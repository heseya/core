<?php

declare(strict_types=1);

namespace Domain\Metadata\Dtos;

use Domain\Metadata\Enums\MetadataType;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

final class MetadataPersonalDto extends Data
{
    public function __construct(
        #[Max(255), AlphaDash]
        public readonly string $name,
        public readonly bool|float|int|string|null $value,
        #[WithCast(EnumCast::class, MetadataType::class)]
        public readonly MetadataType $value_type,
    ) {}
}
