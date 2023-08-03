<?php

namespace App\Rules;

use Closure;
use Domain\Currency\Currency;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class PricesEveryCurrency implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The :attribute is not an array');

            return;
        }

        $currencies = [];

        foreach ($value as $price) {
            $currency = $price['currency'] ?? '';

            if (!in_array($currency, $currencies)) {
                $currencies[] = $currency;
            }
        }

        foreach (Currency::cases() as $currency) {
            if (!in_array($currency->value, $currencies)) {
                $fail("The :attribute has no price for currency {$currency->value}");
            }
        }
    }
}
