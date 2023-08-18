<?php

declare(strict_types=1);

namespace Support\LaravelData\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataProperty;

final class ArrayWrapCast implements Cast
{
    /**
     * @param string[] $context
     *
     * @return string[]|Optional
     */
    public function cast(DataProperty $property, mixed $value, array $context): array|Optional
    {
        return match (true) {
            $value === null => new Optional(),
            is_array($value) => $value,
            default => [$value]
        };
    }
}
