<?php

declare(strict_types=1);

namespace Domain\Metadata\Dtos;

use App\Enums\MetadataType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Support\LaravelData\ExtendedData;

final class MetadataDto extends Data
{
    use ExtendedData;

    public function __construct(
        public readonly string $model_type,
        public readonly string $model_id,

        public readonly string $name,
        public readonly bool|float|int|string|null $value,
        public readonly bool $public,
        #[WithCast(EnumCast::class)]
        public readonly MetadataType $value_type,
    ) {}
}
