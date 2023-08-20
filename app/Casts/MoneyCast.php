<?php

namespace App\Casts;

use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {
        return Money::ofMinor($value, $attributes['currency']);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof Money) {
            throw new InvalidArgumentException('The given value is not a Money instance.');
        }

        // @var Money $value

        return [
            $key => $value->getMinorAmount(),
            'currency' => $value->getCurrency()->getCurrencyCode(),
        ];
    }
}
