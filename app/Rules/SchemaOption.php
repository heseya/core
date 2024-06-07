<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SchemaType;
use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SchemaOption implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (isset($value['schemas'])) {
            /** @var Product|null $product */
            $product = Product::query()->where('id', '=', $value['product_id'])->first();
            if (!$product) {
                $fail(Exceptions::PRODUCT_NOT_FOUND->value);
            }
            /** @var Product $product */
            $schemas = $product->schemas->pluck('id');

            $invalidSchemas = array_diff(array_keys($value['schemas']), $schemas->toArray());

            if (count($invalidSchemas) > 0) {
                $fail(Exceptions::CLIENT_SCHEMA_INVALID->value . ': ' . implode(', ', $invalidSchemas));
            }
            $schemasOptions = $product->schemas()->where('type', '=', SchemaType::SELECT)->get()->pluck('options', 'id');

            $invalidOptions = [];
            foreach ($schemasOptions as $schema => $options) {
                if (array_key_exists($schema, $value['schemas']) && !in_array($value['schemas'][$schema], $options->pluck('id')->toArray())) {
                    $invalidOptions[$schema] = $value['schemas'][$schema];
                }
            }

            if (count($invalidOptions) > 0) {
                $fail(Exceptions::CLIENT_SCHEMA_OPTIONS_INVALID->value . ': ' . implode(', ', $invalidOptions));
            }
        }
    }
}
