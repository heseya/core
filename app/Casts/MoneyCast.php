<?php

namespace App\Casts;

use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Throwable;

class MoneyCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param array<string, mixed> $attributes
     *
     * @throws InvalidArgumentException
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {
        return match (true) {
            $attributes['currency'] !== null && $value !== null => Money::ofMinor($value, $attributes['currency']),
            default => throw new InvalidArgumentException(message: 'The stored value could not be cast into Money instance - currency is missing'),
        };
    }

    /**
     * Prepare the given value for storage.
     *
     * @param array<string, mixed> $attributes
     *
     * @throws InvalidArgumentException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof Money) {
            $currency = $attributes['currency'] ?? $model->currency ?? null;
            $currency = $currency instanceof Currency ? $currency->value : $currency;

            if ($currency) {
                try {
                    $value = Money::of($value, $currency);
                } catch (Throwable $th) {
                    throw new InvalidArgumentException(message: 'The given value could not be cast into Money instance', previous: $th);
                }
            }
        }

        if ($value instanceof Money) {
            return [
                $key => $value->getMinorAmount(),
                'currency' => $value->getCurrency()->getCurrencyCode(),
            ];
        }

        return [
            $key => BigDecimal::of($value)->withPointMovedRight(2)->toInt(),
        ];
    }
}
