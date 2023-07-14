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

readonly class Money implements ValidationRule
{
    public function __construct(
        private ?BigDecimal $min = null,
    ) {}

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The :attribute is not an object');

            return;
        }

        if (!array_key_exists('value', $value)) {
            $fail("The :attribute is missing key 'value'");

            return;
        }

        if (!array_key_exists('currency', $value)) {
            $fail("The :attribute is missing key 'currency'");

            return;
        }

        $currency = Currency::tryFrom($value['currency']);

        if ($currency === null) {
            $fail("The :attribute currency {$value['currency']} is invalid");

            return;
        }

        $amount = $value['value'];
        $money = null;
        try {
            $money = BrickMoney::of($amount, $currency->value);
        } catch (NumberFormatException) {
            $fail('The :attribute value must be decimal string');
        } catch (RoundingNecessaryException) {
            $fail("The :attribute value has too many decimal places for currency {$currency->value}");
        } catch (UnknownCurrencyException) {
            $fail("The :attribute currency {$currency->value} is invalid");
        }

        if ($this->min !== null && $money?->isLessThan($this->min)) {
            $fail("The :attribute value is less than defined minimum: {$this->min}");
        }
    }
}
