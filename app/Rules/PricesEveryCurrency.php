<?php

namespace App\Rules;

use App\Enums\Currency;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money as BrickMoney;
use Closure;
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
