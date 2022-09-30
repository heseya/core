<?php

namespace App\Traits;

use Illuminate\Support\Arr;

trait EnumUtilities
{
    public static function getRandomInstance(): self
    {
        return Arr::random(self::cases());
    }

    public static function getRandomValue(): string
    {
        return self::getRandomInstance()->value;
    }

    public static function getValue(string $key): string|null
    {
        $cases = self::class::cases();

        $result = Arr::first(
            Arr::where($cases, function (self $enum) use ($key) {
                // @phpstan-ignore-next-line
                return $enum->name === $key;
            })
        );

        if ($result === null) {
            return null;
        }

        return $result->value;
    }

    public static function fromKey(string $key): self
    {
        return constant(self::class . '::' . $key);
    }

    public static function hasValue(string $value): bool
    {
        $result = self::tryFrom($value);

        return $result instanceof self;
    }

    public function is(self $enum): bool
    {
        return $this === $enum;
    }

    public function isNot(self $enum): bool
    {
        return !$this->is($enum);
    }

    public function in(array $data): bool
    {
        return in_array($this, $data);
    }
}
