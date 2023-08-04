<?php

declare(strict_types=1);

namespace App\DTO\Metadata;

use App\Enums\MetadataType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

final class MetadataPersonalDto extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly bool|float|int|string|null $value,
        #[WithCast(EnumCast::class)]
        public readonly MetadataType $value_type,
    ) {}
}
