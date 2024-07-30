<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class SchemaAvailable implements ValidationRule
{
    public function __construct(private ?Product $product) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Schema $schema */
        $schema = Schema::query()->findOrFail($value);
        if ($this->product && $schema->product_id && $schema->product_id !== $this->product->getKey()) {
            $fail(__('This :name is already used for other product', ['name' => $schema->name]));
        }
    }
}