<?php

namespace App\Rules;

use Brick\Math\BigDecimal;
use Closure;
use Domains\Currency\Currency;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class ShippingMethodPriceRanges implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The :attribute is not an array');

            return;
        }

        $currencyTable = [];

        foreach ($value as $price_range) {
            $currency = $price_range['currency'] ?? '';

            try {
                $start = BigDecimal::of($price_range['start']);

                if ($start->isZero()) {
                    $currencyTable[$currency] = ($currencyTable[$currency] ?? 0) + 1;
                }
            } catch (Exception) {
            }
        }

        foreach (Currency::cases() as $currency) {
            $startingRanges = $currencyTable[$currency->value] ?? 0;

            if ($startingRanges === 0) {
                $fail("The :attribute has no range starting with 0 for currency {$currency->value}");
            }

            if ($startingRanges >= 2) {
                $fail("The :attribute already has a range starting with 0 for currency {$currency->value}");
            }
        }
    }
}
