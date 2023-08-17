<?php

declare(strict_types=1);

namespace Support\Dtos;

use Spatie\LaravelData\Data;

/**
 * @property class-string<Model> $class
 */
final class ModelIdentityDto extends Data
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $class,
    ) {}
}
