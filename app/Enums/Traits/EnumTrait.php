<?php

namespace App\Enums\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait EnumTrait
{
    public static function names(): array
    {
        return array_column(static::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }

    public static function fromName(string $name): static
    {
        return constant(static::class . '::' . Str::upper($name));
    }

    public static function tryFromName(string $name): ?static
    {
        if (defined(static::class . '::' . Str::upper($name))) {
            return static::fromName($name);
        }

        return null;
    }

    public static function coerce(int|string $valueOrName): ?static
    {
        return match (true) {
            is_int($valueOrName) => static::tryFrom($valueOrName),
            default => static::tryFrom($valueOrName) ?? static::tryFromName($valueOrName),
        };
    }

    public static function getRandomInstance(): static
    {
        return Arr::random(self::cases());
    }

    public static function getRandomValue(): mixed
    {
        return self::getRandomInstance()->value;
    }

    public static function getRandomName(): string
    {
        return self::getRandomInstance()->name;
    }

    public function is(int|self|string $value): bool
    {
        return $this === match (true) {
            is_string($value), is_int($value) => static::tryFrom($value),
            default => $value,
        };
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
