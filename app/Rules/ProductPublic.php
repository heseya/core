<?php

namespace App\Rules;

use App\Models\Product;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class ProductPublic implements DataAwareRule, Rule
{
    private array $data;

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     */
    public function passes($attribute, $value): bool
    {
        /** @var ?Product $product */
        $product = Product::find($value);

        if ($product === null) {
            return false;
        }

        return !empty($this->data['sales_channel_id'])
            ? $product->isPublicForSalesChannel($this->data['sales_channel_id'])
            : $product->public;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function message(): string
    {
        return ':attribute does not exist';
    }
}
