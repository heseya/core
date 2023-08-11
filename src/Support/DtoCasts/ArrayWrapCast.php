<?php

declare(strict_types=1);

namespace Support\DtoCasts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataProperty;

class ArrayWrapCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $context): array|Optional
    {
        return match (true) {
            $value === null => new Optional(),
            is_array($value) => $value,
            default => [$value]
        };
    }
}
