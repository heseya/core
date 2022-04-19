<?php

namespace App\Rules;

use App\Models\Product;
use Illuminate\Contracts\Validation\Rule;

class ProductPublic implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        /** @var ?Product $product */
        $product = Product::find($value);

        if ($product === null) {
            return false;
        }

        return $product->public;
    }

    public function message(): string
    {
        return ':attribute does not exist';
    }
}
