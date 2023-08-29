<?php

namespace App\Rules;

use Closure;
use Domain\Price\Dtos\PriceDto;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

class ConditionOrderValueMaxValuesGreaterThanMinValues implements ValidationRule
{
    public function __construct(
        private readonly array $condition,
        private readonly string $min_values_field = 'min_values',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The :attribute is not an array');

            return;
        }

        if (!array_key_exists($this->min_values_field, $this->condition)) {
            return; // ok
        }

        /** @var array<string, PriceDto> $minPricesForCurrency */
        $minPricesForCurrency = [];
        foreach ($this->condition[$this->min_values_field] as $min_value) {
            try {
                $priceDto = PriceDto::from($min_value);
                $minPricesForCurrency[$priceDto->currency->value] = $priceDto;
            } catch (Throwable $th) {
                $fail("Invalid data in {$this->min_values_field} array, must be PriceDto or compatible");
            }
        }

        /** @var array<string, PriceDto> $maxPricesForCurrency */
        $maxPricesForCurrency = [];
        foreach ($value as $max_value) {
            try {
                $priceDto = PriceDto::from($max_value);
                $maxPricesForCurrency[$priceDto->currency->value] = $priceDto;
            } catch (Throwable $th) {
                $fail('Invalid data in :attribute array, must be PriceDto or compatible');
            }
        }

        $failedCurrencies = '';
        foreach ($maxPricesForCurrency as $currency => $maxPrice) {
            if ($maxPrice->value->isLessThanOrEqualTo($minPricesForCurrency[$currency]->value)) {
                $failedCurrencies .= $currency . ',';
            }
        }

        if (!empty($failedCurrencies)) {
            $fail(":attribute contains values that are lower than values in {$this->min_values_field} for currencies {$failedCurrencies}");
        }
    }
}
