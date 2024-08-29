<?php

declare(strict_types=1);

namespace Support\Dtos;

use Spatie\LaravelData\Data;

final class ModelIdentityDto extends Data
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $class,
    ) {}

    public function getKey(): string
    {
        return $this->uuid;
    }

    public function getMorphClass(): string
    {
        return $this->class;
    }
}
