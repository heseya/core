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
     *
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        /** @var Product $product */
        $product = Product::find($value);

        if ($product === null) {
            return false;
        }

        return $product->isPublic();
    }

    public function message(): string
    {
        return ':attribute does not exist';
    }
}
