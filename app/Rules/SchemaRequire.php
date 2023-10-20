<?php

namespace App\Rules;

use Brick\Math\BigDecimal;
use Closure;
use Domain\ProductSchema\Dtos\OptionDto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class SchemaRequire implements ValidationRule
{

    /**
     * @param OptionDto[] $options
    */
    public function __construct(private readonly ?array $options) {}

    /**
     * @inheritDoc
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === false) {
            return;
        }

        if (!is_array($this->options)) {
            $fail('Option schema is required if schema is set as required');
            return;
        }

        $prices = Arr::pluck($this->options, 'prices.*.value');
        $prices = array_map(fn ($value) => (float) $value, Arr::flatten($prices));

        if (!in_array(BigDecimal::zero()->toFloat(), $prices)) {
            $fail('Option with value 0 is required');
        }
    }
}
