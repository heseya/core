<?php

namespace App\Rules;

use Brick\Math\BigDecimal;
use Closure;
use Domain\ProductSchema\Dtos\OptionDto;
use Illuminate\Contracts\Validation\ValidationRule;
use Spatie\LaravelData\DataCollection;
use Throwable;

class SchemaZeroOptionExistsForEachCurrency implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The :attribute is not an array');

            return;
        }

        try {
            $options = OptionDto::collection($value);
        } catch (Throwable $th) {
            $fail('The :attribute is not OptionDto[] compatible');

            return;
        }

        foreach ($options as $option) {
            if (!$option->prices instanceof DataCollection) {
                continue;
            }
            $all_zeros = true;
            foreach ($option->prices as $price) {
                if (!$price->value->isEqualTo(BigDecimal::zero())) {
                    $all_zeros = false;
                    break;
                }
            }
            if ($all_zeros) {
                return;
            }
        }

        $fail('Option with all prices equal to 0 is required');
    }
}
