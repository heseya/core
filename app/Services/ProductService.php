<?php

namespace App\Services;

use App\Enums\SchemaType;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Support\Collection;

class ProductService implements ProductServiceContract
{
    public function assignItems(Product $product, array|null $itemsIds): Product
    {
        if ($itemsIds !== null) {
            $product->items()->detach();
            $items = new Collection($itemsIds);

            $items->each(
                fn ($item) => $product->items()->attach($item['id'], ['quantity' => $item['quantity']])
            );
        }
        return $product;
    }

    public function getMinMaxPrices(Product $product): array
    {
        $schemaMinMax = $this->getSchemasPrices(
            clone $product->schemas,
            clone $product->schemas,
        );

        return [
            $product->price + $schemaMinMax[0],
            $product->price + $schemaMinMax[1],
        ];
    }

    public function updateMinMaxPrices(Product $product)
    {
        $productMinMaxPrices = $this->getMinMaxPrices($product);
        $product->update([
            'price_min' => $productMinMaxPrices[0],
            'price_max' => $productMinMaxPrices[1],
        ]);
    }

    private function getSchemasPrices(
        Collection $allSchemas,
        Collection $remainingSchemas,
        array $values = [],
    ): array {
        if ($remainingSchemas->isNotEmpty()) {
            /** @var Schema $schema */
            $schema = $remainingSchemas->pop();

            $getBestSchemasPrices = fn ($newValues) => $this->getBestSchemasPrices(
                $allSchemas,
                $remainingSchemas,
                $values,
                $schema,
                $newValues,
            );

            $required = $schema->required;
            $options = $schema->options->map(
                fn (Option $option) => $option->getKey(),
            )->toArray();
            $valueMinMax = [$schema->min, $schema->max];

            $minmax = match ($schema->type->value) {
                default => $getBestSchemasPrices(
                    $required ? ['filled'] : [null, 'filled'],
                ),
                SchemaType::BOOLEAN => $getBestSchemasPrices([true, false]),
                SchemaType::SELECT => $getBestSchemasPrices(
                    $required ? $options : array_merge($options, [null]),
                ),
                SchemaType::MULTIPLY, SchemaType::MULTIPLY_SCHEMA => $getBestSchemasPrices(
                    $required ? $valueMinMax : array_merge($valueMinMax, [null]),
                ),
            };
        } else {
            $price = $allSchemas->reduce(
                fn (float $carry, Schema $current) => $carry + $current->getPrice(
                    $values[$current->getKey()],
                    $values,
                ),
                0,
            );

            $minmax = [
                $price,
                $price,
            ];
        }

        return $minmax;
    }

    private function getBestSchemasPrices(
        Collection $allSchemas,
        Collection $remainingSchemas,
        array $currentValues,
        Schema $schema,
        array $values,
    ): array {
        return $this->bestMinMax(Collection::make($values)->map(
            fn ($value) => $this->getSchemasPrices(
                $allSchemas,
                clone $remainingSchemas,
                $currentValues + [
                    $schema->getKey() => $value,
                ],
            ),
        ));
    }

    private function bestMinMax(Collection $minmaxCol): array
    {
        return [
            $minmaxCol->reduce(function (?float $carry, array $current) {
                if ($carry === null) {
                    return $current[0];
                }

                return $current[0] < $carry ? $current[0] : $carry;
            }),
            $minmaxCol->reduce(function (?float $carry, array $current) {
                if ($carry === null) {
                    return $current[1];
                }

                return $current[1] > $carry ? $current[1] : $carry;
            }),
        ];
    }
}
