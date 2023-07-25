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

readonly class Price implements ValidationRule
{
    /**
     * @param string[] $amountKeys
     */
    public function __construct(
        private array $amountKeys,
        private ?BigDecimal $min = null,
    ) {}

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $currency = $this->validateCurrency($value, $fail);

        if ($currency === null) {
            return;
        }

        foreach ($this->amountKeys as $amountKey) {
            $this->validateAmount($value, $amountKey, $currency, $fail);
        }
    }

    public function validateCurrency(mixed $value, Closure $fail): ?Currency
    {
        if (!is_array($value)) {
            $fail('The :attribute is not an object');

            return null;
        }

        if (!array_key_exists('currency', $value)) {
            $fail("The :attribute is missing key 'currency'");

            return null;
        }

        $currency = Currency::tryFrom($value['currency']);

        if ($currency === null) {
            $fail("The :attribute currency {$value['currency']} is invalid");
        }

        return $currency;
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function validateAmount(mixed $value, string $amountKey, Currency $currency, Closure $fail): void
    {
        if (!array_key_exists($amountKey, $value)) {
            $fail("The :attribute is missing key '{$amountKey}'");

            return;
        }

        $amount = $value[$amountKey];
        $money = null;
        try {
            $money = BrickMoney::of($amount, $currency->value);
        } catch (NumberFormatException) {
            $fail("The :attribute {$amountKey} must be decimal string");
        } catch (RoundingNecessaryException) {
            $fail("The :attribute {$amountKey} has too many decimal places for currency {$currency->value}");
        } catch (UnknownCurrencyException) {
            $fail("The :attribute currency {$currency->value} for {$amountKey} is invalid");
        }

        if ($this->min !== null && $money?->isLessThan($this->min)) {
            $fail("The :attribute value is less than defined minimum: {$this->min}");
        }
    }
}
