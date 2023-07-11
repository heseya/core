<?php

namespace App\Rules;

use App\Enums\Currency;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money as BrickMoney;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Money implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute needs to be a decimal string');

            return;
        }

        $defaultCurrency = Currency::DEFAULT->value;
        try {
            BrickMoney::of($value, $defaultCurrency);
        } catch (NumberFormatException) {
            $fail('The :attribute needs to be a decimal string');
        } catch (RoundingNecessaryException) {
            $fail("The :attribute has too many decimal places for currency {$defaultCurrency}");
        } catch (UnknownCurrencyException) {
            $fail("The {$defaultCurrency} currency is invalid");
        }
    }
}
