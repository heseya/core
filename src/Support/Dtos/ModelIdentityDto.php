<?php

declare(strict_types=1);

namespace Support\Dtos;

use Illuminate\Database\Eloquent\Model;
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

    public function getKey(): string
    {
        return $this->uuid;
    }

    public function getMorphClass(): string
    {
        return (new $this->class())->getMorphClass();
    }

    public function getInstance(): ?Model
    {
        return $this->class::find($this->uuid);
    }
}
