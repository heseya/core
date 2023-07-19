<?php

namespace App\Rules;

use App\Enums\Currency;
use Brick\Math\BigDecimal;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use function Sentry\trace;

class ShippingMethodPriceRanges implements ValidationRule
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
                    $currencyTable[$currency] = true;
                }
            } catch (Exception) {}
        }

        foreach (Currency::cases() as $currency) {
            if (($currencyTable[$currency->value] ?? false) !== true) {
                $fail("The :attribute has no range starting with 0 for currency {$currency->value}");
            }
        }
    }
}
