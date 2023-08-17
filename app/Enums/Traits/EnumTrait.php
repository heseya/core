<?php

namespace App\Enums\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionEnum;
use Throwable;

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
        $reflection = new ReflectionEnum(static::class);

        $name = Str::upper($name);

        return $reflection->getCase($name)->getValue(); // @phpstan-ignore-line
    }

    public static function tryFromName(string $name): ?static
    {
        $reflection = new ReflectionEnum(static::class);

        $name = Str::upper($name);

        return $reflection->hasCase($name)            // @phpstan-ignore-line
            ? $reflection->getCase($name)->getValue() // @phpstan-ignore-line
            : null;                                   // @phpstan-ignore-line
    }

    public static function coerce(int|string $valueOrName): ?static
    {
        $reflection = new ReflectionEnum(static::class);
        $backingType = (string) $reflection->getBackingType();

        return match ($backingType) {
            'int', 'integer' => is_int($valueOrName) ? static::tryFrom($valueOrName) : static::tryFromName($valueOrName),
            'string' => is_string($valueOrName) ? static::tryFrom($valueOrName) ?? static::tryFromName($valueOrName) : null,
            default => null,
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
        try {
            return $this === match (true) {
                is_string($value), is_int($value) => static::tryFrom($value),
                default => $value,
            };
        } catch (Throwable $th) {
            return false;
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
